<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ImportModule\Control\ImportDetail;

use App\ReadModel\Import\ImportView;

interface ImportDetailControlFactoryInterface
{
	/**
	 * @param \App\ReadModel\Import\ImportView $importView
	 *
	 * @return \App\Web\AdminModule\ImportModule\Control\ImportDetail\ImportDetailControl
	 */
	public function create(ImportView $importView): ImportDetailControl;
}