<?php

declare(strict_types=1);

namespace App\Bridge\Monolog\Formatter;

use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;

final class ConsoleFormatter extends LineFormatter
{
    public const string SIMPLE_FORMAT = "%extra.start_tag%[%datetime%] %level_name%:%extra.end_tag% %message% %context%\n";

    public function format(LogRecord $record): string
    {
        $level = $record->level->value;

        if ($level >= Level::Error->value) {
            $record->extra['start_tag'] = '<error>';
            $record->extra['end_tag'] = '</error>';
        } elseif ($level >= Level::Notice->value) {
            $record->extra['start_tag'] = '<comment>';
            $record->extra['end_tag'] = '</comment>';
        } elseif ($level >= Level::Info->value) {
            $record->extra['start_tag'] = '<info>';
            $record->extra['end_tag'] = '</info>';
        } else {
            $record->extra['start_tag'] = '';
            $record->extra['end_tag'] = '';
        }

        return parent::format($record);
    }
}
