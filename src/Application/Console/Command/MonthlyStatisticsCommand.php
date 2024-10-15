<?php

declare(strict_types=1);

namespace App\Application\Console\Command;

use App\ReadModel\Consent\CountConsentsByCategoriesPerMonthQuery;
use App\ReadModel\Consent\CountFullyNegativeConsentsPerMonthQuery;
use App\ReadModel\Consent\CountFullyPositiveConsentsPerMonthQuery;
use App\ReadModel\Consent\MonthlyStatistics;
use App\ReadModel\Project\GetProjectByCodeQuery;
use App\ReadModel\Project\ProjectView;
use DateTimeImmutable;
use NumberFormatter;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Calculate number of users who have agreed to all categories of cookies:
 *
 * $ bin/console cmp:monthly-statistics <project-code> --accepted-all
 *
 *
 * Calculate the number of users who did not accept any cookie category:
 *
 * $ bin/console cmp:monthly-statistics <project-code> --rejected-all
 *
 *
 * Calculate the number of users who have agreed with categories `ad_storage` and `analytics_storage`:
 *
 * $ bin/console cmp:monthly-statistics <project-code> --by-categories "ad_storage,analytics_storage"
 *
 *
 * Calculate the number of users who have agreed with the category `ad_storage` but rejected the category `analytics_storage`:
 *
 * $ bin/console cmp:monthly-statistics <project-code> --by-categories "ad_storage,!analytics_storage"
 *
 *
 * The requested statistics can be returned in single runtime:
 *
 * $ bin/console cmp:monthly-statistics <project-code> --accepted-all --rejected-all --by-categories "ad_storage,analytics_storage" --by-categories "ad_storage,!analytics_storage"
 *
 * @phpstan-import-type MonthlyStatisticsArray from MonthlyStatistics
 */
final class MonthlyStatisticsCommand extends Command
{
    private const string FORMAT_TABLE = 'table';
    private const string FORMAT_CSV = 'csv';

    private const array FORMATS = [
        self::FORMAT_TABLE,
        self::FORMAT_CSV,
    ];

    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('cmp:monthly-statistics')
            ->setDescription('Shows monthly statistics for specified year.')
            ->addArgument(
                name: 'project-code',
                mode: InputArgument::REQUIRED,
                description: 'The project code.',
            )
            ->addOption(
                name: 'accepted-all',
                mode: InputOption::VALUE_NONE,
                description: 'Calculates number of users who have agreed to all categories of cookies.',
            )
            ->addOption(
                name: 'rejected-all',
                mode: InputOption::VALUE_NONE,
                description: 'Calculates the number of users who did not accept any cookie category.',
            )
            ->addOption(
                name: 'by-categories',
                mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: 'Calculates the number of users who have agreed to given categories. Use the category codes separated by a comma as the value.',
            )
            ->addOption(
                name: 'unique',
                mode: InputOption::VALUE_NONE,
                description: 'Count only unique (last) consents by user in a particular month.',
            )
            ->addOption(
                name: 'year',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Year for which statistics are to be calculated.',
                default: (new DateTimeImmutable('now'))->format('Y'),
            )
            ->addOption(
                name: 'format',
                mode: InputOption::VALUE_REQUIRED,
                description: sprintf(
                    'Output format, allowed values are "%s".',
                    implode('", "', self::FORMATS),
                ),
                default: self::FORMAT_TABLE,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $projectCode = $input->getArgument('project-code');
        $year = (int) $input->getOption('year');
        $format = $input->getOption('format');
        $unique = $input->getOption('unique');
        $acceptedAll = $input->getOption('accepted-all');
        $rejectedAll = $input->getOption('rejected-all');
        $byCategories = $input->getOption('by-categories');

        assert(
            is_string($projectCode)
            && is_string($format)
            && is_bool($unique)
            && is_bool($acceptedAll)
            && is_bool($rejectedAll)
            && is_array($byCategories),
        );

        if (false === $acceptedAll && false === $rejectedAll && empty($byCategories)) {
            $style->getErrorStyle()->error(<<<MESSAGE
                No statistics definitions provided, please use almost one of the following options:
                --accepted-all
                --rejected-all
                --by-categories="<comma separated list of category codes>"
            MESSAGE);

            return 1;
        }

        if (!in_array($format, self::FORMATS)) {
            $style->getErrorStyle()->error(sprintf(
                "Format \"%s\" is not supported. Allowed formats are:\n%s",
                $format,
                implode("\n", self::FORMATS),
            ));

            return 1;
        }

        $projectId = $this->getProjectId($projectCode);

        if (null === $projectId) {
            $style->getErrorStyle()->error(sprintf(
                'Project "%s" not found.',
                $projectCode,
            ));

            return 1;
        }

        $this->addOutputHeader($style, $format);

        if ($acceptedAll) {
            $this->addOutput(
                style: $style,
                caption: $unique ? 'Unique users who have accepted all cookies' : 'Users who have accepted all cookies',
                months: $this->calculateAcceptedAll(
                    projectId: $projectId,
                    year: $year,
                    unique: $unique,
                ),
                format: $format,
            );
        }

        if ($rejectedAll) {
            $this->addOutput(
                style: $style,
                caption: $unique ? 'Unique users who have rejected all cookies' : 'Users who have rejected all cookies',
                months: $this->calculateRejectedAll(
                    projectId: $projectId,
                    year: $year,
                    unique: $unique,
                ),
                format: $format,
            );
        }

        foreach ($byCategories as $byCategoriesList) {
            [$acceptedCategories, $rejectedCategories] = $this->convertCategoryList($byCategoriesList);
            $captionParts = [];

            if (!empty($acceptedCategories)) {
                $captionParts[] = sprintf(
                    'accepted categories ["%s"]',
                    implode(', ', $acceptedCategories),
                );
            }

            if (!empty($rejectedCategories)) {
                $captionParts[] = sprintf(
                    'rejected categories ["%s"]',
                    implode(', ', $rejectedCategories),
                );
            }

            $this->addOutput(
                style: $style,
                caption: sprintf(
                    $unique ? 'Unique users who %s' : 'Users who %s',
                    implode(' and ', $captionParts),
                ),
                months: $this->calculateByCategories(
                    projectId: $projectId,
                    year: $year,
                    unique: $unique,
                    acceptedCategories: $acceptedCategories,
                    rejectedCategories: $rejectedCategories,
                ),
                format: $format,
            );
        }

        $this->addOutputFooter($style, $format);

        return 0;
    }

    private function addOutputHeader(SymfonyStyle $style, string $format): void
    {
        if ($format === self::FORMAT_CSV) {
            $style->writeln(implode(
                ';',
                array_merge(
                    [
                        'statistic',
                    ],
                    array_keys(
                        array_fill(1, 12, ''),
                    ),
                ),
            ));
        } else {
            $style->newLine();
        }
    }

    private function addOutputFooter(SymfonyStyle $style, string $format): void
    {
        if ($format === self::FORMAT_TABLE) {
            $style->success('Done');
        }
    }

    /**
     * @param MonthlyStatisticsArray $months
     */
    private function addOutput(SymfonyStyle $style, string $caption, array $months, string $format): void
    {
        switch ($format) {
            case self::FORMAT_TABLE:
                $formatter = new NumberFormatter('cs_CZ', NumberFormatter::DEFAULT_STYLE);

                $months = array_map(
                    static fn (int $month): string => $formatter->format($month),
                    $months,
                );

                $table = $style->createTable()
                    ->setHeaders(array_keys(
                        array_fill(1, 12, ''),
                    ))
                    ->setColumnWidths(array_fill(0, 12, 8))
                    ->addRow($months);

                $table->getStyle()->setPadType(STR_PAD_BOTH)
                    ->setDefaultCrossingChar('+')
                    ->setVerticalBorderChars('|');

                $style->section($caption);

                $table->render();
                $style->newLine();

                break;

            case self::FORMAT_CSV:
                $style->writeln(implode(
                    ';',
                    array_merge(
                        [
                            '"' . str_replace('"', '""', $caption) . '"',
                        ],
                        $months,
                    ),
                ));

                break;
        }
    }

    /**
     * @return MonthlyStatisticsArray
     */
    private function calculateAcceptedAll(string $projectId, int $year, bool $unique): array
    {
        $result = $this->queryBus->dispatch(CountFullyPositiveConsentsPerMonthQuery::create(
            projectId: $projectId,
            year: $year,
            unique: $unique,
        ));
        assert($result instanceof MonthlyStatistics);

        return $result->toArray();
    }

    /**
     * @return MonthlyStatisticsArray
     */
    private function calculateRejectedAll(string $projectId, int $year, bool $unique): array
    {
        $result = $this->queryBus->dispatch(CountFullyNegativeConsentsPerMonthQuery::create(
            projectId: $projectId,
            year: $year,
            unique: $unique,
        ));
        assert($result instanceof MonthlyStatistics);

        return $result->toArray();
    }

    /**
     * @param array<int, string> $acceptedCategories
     * @param array<int, string> $rejectedCategories
     *
     * @return MonthlyStatisticsArray
     */
    private function calculateByCategories(string $projectId, int $year, bool $unique, array $acceptedCategories, array $rejectedCategories): array
    {
        $result = $this->queryBus->dispatch(CountConsentsByCategoriesPerMonthQuery::create(
            projectId: $projectId,
            year: $year,
            unique: $unique,
            acceptedCategories: $acceptedCategories,
            rejectedCategories: $rejectedCategories,
        ));
        assert($result instanceof MonthlyStatistics);

        return $result->toArray();
    }

    /**
     * @return array{
     *     0: array<int, string>,
     *     1: array<int, string>,
     * }
     */
    private function convertCategoryList(string $list): array
    {
        $accepted = [];
        $rejected = [];

        foreach (explode(',', $list) as $categoryCode) {
            $categoryCode = trim($categoryCode);

            if (str_starts_with($categoryCode, '!')) {
                $rejected[] = substr($categoryCode, 1);
            } else {
                $accepted[] = $categoryCode;
            }
        }

        return [
            $accepted,
            $rejected,
        ];
    }

    private function getProjectId(string $projectCode): ?string
    {
        $project = $this->queryBus->dispatch(GetProjectByCodeQuery::create($projectCode));

        return $project instanceof ProjectView ? $project->id->toString() : null;
    }
}
