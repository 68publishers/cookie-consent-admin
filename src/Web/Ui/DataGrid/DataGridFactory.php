<?php

declare(strict_types=1);

namespace App\Web\Ui\DataGrid;

use App\ReadModel\DataGridQueryInterface;
use App\Web\Ui\DataGrid\DataSource\ReadModelDataSource;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;

final readonly class DataGridFactory implements DataGridFactoryInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function create(?DataGridQueryInterface $query = null): DataGrid
    {
        $dataGrid = new DataGrid();

        if (null !== $query) {
            $dataGrid->setDataSource(new ReadModelDataSource($query, $this->queryBus));
        }

        $dataGrid->setItemsPerPageList([10, 20, 50], false);
        $dataGrid->setStrictSessionFilterValues(false);

        $dataGrid->setCustomPaginatorTemplate(__DIR__ . '/../templates/datagrid/paginator.latte');

        return $dataGrid;
    }
}
