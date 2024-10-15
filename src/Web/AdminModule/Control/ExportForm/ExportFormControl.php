<?php

declare(strict_types=1);

namespace App\Web\AdminModule\Control\ExportForm;

use App\Application\DataProcessor\DataProcessFactory;
use App\Application\DataProcessor\Exception\DataReaderExceptionInterface;
use App\Application\DataProcessor\Exception\WriterException;
use App\Application\DataProcessor\Write\Writer\CsvWriter;
use App\Application\DataProcessor\Write\Writer\JsonWriter;
use App\Bridge\Nette\Http\DownloadContentResponse;
use App\Web\AdminModule\Control\ExportForm\Callback\ExportCallbackInterface;
use App\Web\AdminModule\Control\ExportForm\Event\ExportFormProcessingFailedEvent;
use App\Web\AdminModule\Control\ExportForm\Event\SuccessfullyExportedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use App\Web\Utils\TranslatorUtils;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

final class ExportFormControl extends Control
{
    use FormFactoryOptionsTrait;

    private const string FORMAT_CSV = 'csv';
    private const string FORMAT_JSON = 'json';

    private const array FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_JSON,
    ];

    private const CSV_SEPARATORS = [
        'comma' => ',',
        'semicolon' => ';',
        'tabulator' => "\t",
        'space' => ' ',
        'pipe' => '|',
    ];

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly DataProcessFactory $dataProcessFactory,
        private readonly ExportCallbackInterface $exportCallback,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($this->getPrefixedTranslator());

        $form->addSelect('format', 'format.field')
            ->setItems(array_combine(
                self::FORMATS,
                TranslatorUtils::translateArray($translator, 'format.item.', self::FORMATS),
            ))
            ->setTranslator(null)
            ->setRequired('format.required')
            ->setDefaultValue(self::FORMAT_CSV)
            ->addCondition($form::Equal, self::FORMAT_CSV)
                ->toggle('#' . $this->getUniqueId() . '-separator-container');

        $form->addSelect('separator', 'separator.field')
            ->setItems(array_combine(
                array_keys(self::CSV_SEPARATORS),
                TranslatorUtils::translateArray($translator, 'separator.item.', array_keys(self::CSV_SEPARATORS)),
            ))
            ->setTranslator(null)
            ->setDefaultValue('comma')
            ->setOption('id', $this->getUniqueId() . '-separator-container')
            ->addConditionOn($form['format'], $form::Equal, self::FORMAT_CSV)
                ->setRequired('separator.required');

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('export', 'export.field')
            ->setOption('no-spinner', true);

        $form->onSuccess[] = function (Form $form): void {
            $this->export($form);
        };

        return $form;
    }

    /**
     * @throws AbortException
     */
    private function export(Form $form): void
    {
        $values = $form->values;
        $format = $values->format;
        $options = [];
        $contentType = 'application/octet-stream';

        switch ($format) {
            case self::FORMAT_JSON:
                $options = [
                    JsonWriter::OPTION_PRETTY => true,
                    JsonWriter::OPTION_UNESCAPED_UNICODE => true,
                ];
                $contentType = 'application/json';

                break;
            case self::FORMAT_CSV:
                $options = [
                    CsvWriter::INCLUDE_BOM => true,
                    CsvWriter::OPTION_DELIMITER => self::CSV_SEPARATORS[$values->separator],
                ];
                $contentType = 'text/csv';

                break;
        }

        try {
            $content = ($this->exportCallback)($this->dataProcessFactory, $format, $options);
        } catch (DataReaderExceptionInterface|WriterException $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new ExportFormProcessingFailedEvent($e));
            $form->addError('error.processing_fail');
            $this->redrawControl();

            return;
        }

        $this->dispatchEvent(new SuccessfullyExportedEvent());

        $filename = $this->exportCallback->name() . '.' . $format;
        $presenter = $this->getPresenter();

        assert($presenter instanceof Presenter);

        $presenter->sendResponse(new DownloadContentResponse($content, $filename, $contentType));
    }
}
