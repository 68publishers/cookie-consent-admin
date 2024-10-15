<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ImportModule\Control\ImportForm;

use App\Application\DataProcessor\Read\DataReaderFactoryInterface;
use App\Application\DataProcessor\Read\Reader\CsvReader;
use App\Application\Import\Helper\KnownDescriptors;
use App\Application\Import\ImportOptions;
use App\Application\Import\RunnerInterface;
use App\Web\AdminModule\ImportModule\Control\ImportForm\Event\ImportEndedEvent;
use App\Web\AdminModule\ImportModule\Control\ImportForm\Event\ImportFormProcessingFailedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use App\Web\Utils\TranslatorUtils;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use SixtyEightPublishers\UserBundle\Bridge\Nette\Security\Identity;
use Throwable;

final class ImportFormControl extends Control
{
    use FormFactoryOptionsTrait;

    private const string FORMAT_CSV = 'csv';
    private const string FORMAT_JSON = 'json';

    private const array FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_JSON,
    ];

    private const array CSV_SEPARATORS = [
        'auto' => null,
        'comma' => ',',
        'semicolon' => ';',
        'tabulator' => "\t",
        'space' => ' ',
        'pipe' => '|',
    ];

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RunnerInterface $runner,
        private readonly DataReaderFactoryInterface $dataReaderFactory,
        private readonly ?string $strictImportType = null,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();

        $form->setTranslator($this->getPrefixedTranslator());

        $fileField = $form->addUpload('file', 'file.field')
            ->setRequired('file.required');

        $typeField = $form->addSelect('type', 'type.field')
            ->setPrompt('-------')
            ->setItems(array_combine(
                KnownDescriptors::ALL,
                TranslatorUtils::translateArray($translator, '//imports.name.', KnownDescriptors::ALL),
            ))
            ->setTranslator(null)
            ->setRequired('type.required');

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
            ->setDefaultValue('auto')
            ->setOption('id', $this->getUniqueId() . '-separator-container')
            ->addConditionOn($form['format'], $form::Equal, self::FORMAT_CSV)
                ->setRequired('separator.required');

        $fileField
            ->addConditionOn($form['format'], $form::Equal, self::FORMAT_CSV)
                ->addRule($form::MimeType, 'file.rule.mime_type.csv', ['text/csv', 'text/plain'])
                ->endCondition()
            ->addConditionOn($form['format'], $form::Equal, self::FORMAT_JSON)
                ->addRule($form::MimeType, 'file.rule.mime_type.json', ['application/json', 'text/plain'])
                ->endCondition();

        if (null !== $this->strictImportType) {
            $typeField
                ->setDisabled()
                ->setOmitted(false)
                ->setDefaultValue($this->strictImportType);
        }

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('import', 'import.field');

        $form->onSuccess[] = function (Form $form): void {
            $this->import($form);
        };

        return $form;
    }

    private function import(Form $form): void
    {
        $values = $form->values;

        try {
            $type = $values->type;
            $format = $values->format;
            $filename = $values->file->getTemporaryFile();

            $file = $values->file;
            assert($file instanceof FileUpload);

            $options = self::FORMAT_CSV === $format ? [
                CsvReader::OPTION_HAS_HEADER => true,
                CsvReader::OPTION_DELIMITER => self::CSV_SEPARATORS[$values->separator],
            ] : [];

            $reader = $this->dataReaderFactory->fromFile($format, $filename, $options);
            $options = ImportOptions::create($type)
                ->withAuthorId($this->getAuthorId())
                ->withBatchSize(50);

            $state = $this->runner->run($reader, $options);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new ImportFormProcessingFailedEvent($e));
            $form->addError('error.processing_fail');
            $this->redrawControl();

            return;
        }

        $this->dispatchEvent(new ImportEndedEvent($state));
        $this->redrawControl();
    }

    private function getAuthorId(): ?string
    {
        $user = $this->getUser();

        if (!$user->isLoggedIn()) {
            return null;
        }

        $identity = $user->getIdentity();
        assert($identity instanceof Identity);

        return $identity->id()->toString();
    }
}
