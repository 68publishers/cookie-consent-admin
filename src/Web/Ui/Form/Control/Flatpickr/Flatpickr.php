<?php

declare(strict_types=1);

namespace App\Web\Ui\Form\Control\Flatpickr;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\AssertionException;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Throwable;

final class Flatpickr extends TextInput
{
    public const string ATTRIBUTE_MODE = 'data-mode';
    public const string ATTRIBUTE_NO_CALENDAR = 'data-no-calendar';
    public const string ATTRIBUTE_ENABLE_TIME = 'data-enable-time';
    public const string ATTRIBUTE_DATE_FORMAT = 'data-date-format';
    public const string ATTRIBUTE_TIME_24_HR = 'data-time_24hr';
    public const string ATTRIBUTE_ALLOW_INPUT = 'data-allow-input';
    public const string ATTRIBUTE_DEFAULT_HOUR = 'data-default-hour';
    public const string ATTRIBUTE_DEFAULT_MINUTE = 'data-default-minute';

    public const string MODE_SINGLE = 'single';
    public const string MODE_MULTIPLE = 'multiple';
    public const string MODE_RANGE = 'range';
    public const string MODE_DEFAULT = self::MODE_SINGLE;

    private array $attributes = [
        'x-data' => '',
        'x-flatpickr' => '',
        self::ATTRIBUTE_MODE => self::MODE_DEFAULT,
        self::ATTRIBUTE_NO_CALENDAR => false,
        self::ATTRIBUTE_ENABLE_TIME => false,
        self::ATTRIBUTE_DATE_FORMAT => 'j.n.Y',
        self::ATTRIBUTE_TIME_24_HR => false,
        self::ATTRIBUTE_ALLOW_INPUT => false,
        self::ATTRIBUTE_DEFAULT_HOUR => null,
        self::ATTRIBUTE_DEFAULT_MINUTE => null,
    ];

    /** @var NULL|DateTimeInterface|array<DateTimeInterface> */
    private DateTimeInterface|array|null $realValue = null;

    private bool $returnRealValue = true;

    private ?DateTimeZone $dateTimeZone = null;

    public function __construct(?string $label = null)
    {
        parent::__construct($label);

        $this->setNullable();
    }

    public static function create(?string $label = null): self
    {
        return new self($label);
    }

    /**
     * @throws Throwable
     */
    public function setValue($value): self
    {
        try {
            if (!empty($value)) {
                if (is_string($value)) {
                    $this->realValue = $this->rawToReal($value);
                } else {
                    $this->realValue = $value;
                    $value = $this->realToRaw($value);
                }
            } else {
                $this->realValue = null;
            }

            /** @noinspection PhpInternalEntityUsedInspection */
            parent::setValue($value);
        } catch (FlatpickrException $e) {
            if (false === $this->attributes[self::ATTRIBUTE_ALLOW_INPUT]) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * @return DateTimeInterface|array<DateTimeInterface>|NULL|string
     */
    public function getValue(): DateTimeInterface|array|null|string
    {
        return true === $this->returnRealValue
            ? $this->realValue
            : parent::getValue();
    }

    public function getControl(): Html
    {
        return parent::getControl()
            ->addAttributes(array_map(
                static fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value,
                array_filter($this->attributes, static fn ($value) => null !== $value),
            ));
    }

    /**
     * @throws FlatpickrException
     */
    public function setMode(string $mode): self
    {
        if (!in_array($mode, [self::MODE_SINGLE, self::MODE_MULTIPLE, self::MODE_RANGE], true)) {
            throw $this->error(sprintf('Mode %s is not supported', $mode));
        }

        $this->attributes[self::ATTRIBUTE_MODE] = $mode;

        return $this;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->attributes[self::ATTRIBUTE_DATE_FORMAT] = $dateFormat;

        return $this;
    }

    public function setNoCalendar(bool $noCalendar = true): self
    {
        $this->attributes[self::ATTRIBUTE_NO_CALENDAR] = $noCalendar;

        return $this;
    }

    public function setEnableTime(bool $enableTime = true, bool $time24hr = true): self
    {
        $this->attributes[self::ATTRIBUTE_ENABLE_TIME] = $enableTime;
        $this->attributes[self::ATTRIBUTE_TIME_24_HR] = $time24hr;

        return $this;
    }

    public function setAllowInput(bool $allowInput = true): self
    {
        $this->attributes[self::ATTRIBUTE_ALLOW_INPUT] = $allowInput;

        return $this;
    }

    public function setDefaultTime(int $hour, int $minute): self
    {
        $this->attributes[self::ATTRIBUTE_DEFAULT_HOUR] = $hour;
        $this->attributes[self::ATTRIBUTE_DEFAULT_MINUTE] = $minute;

        return $this;
    }

    public function setReturnRealValue(bool $returnRealValue): self
    {
        $this->returnRealValue = $returnRealValue;

        return $this;
    }

    public function setDateTimeZone(DateTimeZone $dateTimeZone): self
    {
        $this->dateTimeZone = $dateTimeZone;

        return $this;
    }

    /**
     * @return DateTimeInterface|array<DateTimeInterface>
     * @throws Throwable
     */
    private function rawToReal(string $rawValue): DateTimeInterface|array
    {
        switch ($this->attributes[self::ATTRIBUTE_MODE]) {
            case self::MODE_SINGLE:

                return $this->createDateTimeFromString($rawValue);
            case self::MODE_MULTIPLE:

                return array_map(function (string $dateTime) {
                    return $this->createDateTimeFromString($dateTime);
                }, explode(',', $rawValue));
            case self::MODE_RANGE:
                $values = array_map(function (string $dateTime) {
                    return $this->createDateTimeFromString($dateTime);
                }, explode('to', $rawValue));

                if (2 !== count($values)) {
                    throw $this->error('Passed value must be in format "[date] to [date]" if mode is set to "range"');
                }

                return [
                    'from' => array_shift($values),
                    'to' => array_shift($values),
                ];
        }

        throw $this->error(sprintf(
            'Invalid mode %s',
            $this->attributes[self::ATTRIBUTE_MODE],
        ));
    }

    /**
     * @throws FlatpickrException
     */
    private function realToRaw(mixed $realValue): string
    {
        $this->assertRealValue($realValue);
        switch ($this->attributes[self::ATTRIBUTE_MODE]) {
            case self::MODE_SINGLE:

                return $realValue->setTimezone($this->getDateTimeZone())->format($this->attributes[self::ATTRIBUTE_DATE_FORMAT]);
            case self::MODE_MULTIPLE:

                return implode(', ', array_map(function (DateTimeInterface $dateTime) {
                    return $dateTime->setTimezone($this->getDateTimeZone())->format($this->attributes[self::ATTRIBUTE_DATE_FORMAT]);
                }, $realValue));
            case self::MODE_RANGE:

                return sprintf(
                    '%s to %s',
                    $realValue['from']->setTimezone($this->getDateTimeZone())->format($this->attributes[self::ATTRIBUTE_DATE_FORMAT]),
                    $realValue['to']->setTimezone($this->getDateTimeZone())->format($this->attributes[self::ATTRIBUTE_DATE_FORMAT]),
                );
        }

        throw $this->error(sprintf(
            'Invalid mode %s',
            $this->attributes[self::ATTRIBUTE_MODE],
        ));
    }

    /**
     * @throws Throwable
     */
    private function createDateTimeFromString(string $dateTime): DateTimeInterface
    {
        try {
            $dateTime = DateTime::createFromFormat(
                $this->attributes[self::ATTRIBUTE_DATE_FORMAT],
                Strings::trim($dateTime),
                $this->getDateTimeZone(),
            );

            if (false === $dateTime) {
                throw $this->error('Invalid Datetime string representation value.');
            }

            if (false === $this->attributes[self::ATTRIBUTE_ENABLE_TIME]) {
                $dateTime->setTime(0, 0);
            }

            return $dateTime;
        } catch (Throwable $e) {
            if (!$e instanceof FlatpickrException) {
                $e = $this->error($e->getMessage());
            }

            throw $e;
        }
    }

    private function error(string $message): FlatpickrException
    {
        return new FlatpickrException($message);
    }

    /**
     * @throws FlatpickrException
     */
    private function assertRealValue($realValue): void
    {
        try {
            switch ($this->attributes[self::ATTRIBUTE_MODE]) {
                case self::MODE_SINGLE:
                    Validators::assert($realValue, DateTimeInterface::class);

                    break;
                case self::MODE_MULTIPLE:
                    Validators::assert($realValue, DateTimeInterface::class . '[]');

                    break;
                case self::MODE_RANGE:
                    Validators::assertField($realValue, 'from', DateTimeInterface::class);
                    Validators::assertField($realValue, 'to', DateTimeInterface::class);

                    break;
            }
        } catch (AssertionException $e) {
            throw $this->error(sprintf(
                'Invalid value passed to method %s. Original message: %s',
                __METHOD__,
                $e->getMessage(),
            ));
        }
    }

    private function getDateTimeZone(): DateTimeZone
    {
        if (null === $this->dateTimeZone) {
            $this->setDateTimeZone(new DateTimeZone('UTC'));
        }

        return $this->dateTimeZone;
    }
}
