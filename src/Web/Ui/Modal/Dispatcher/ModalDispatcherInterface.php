<?php

declare(strict_types=1);

namespace App\Web\Ui\Modal\Dispatcher;

use JsonSerializable;
use Nette\ComponentModel\IComponent;
use SixtyEightPublishers\EventDispatcherExtra\EventDispatcherAwareInterface;

interface ModalDispatcherInterface extends JsonSerializable, EventDispatcherAwareInterface
{
    public const string PARAMS_ON_OPEN = 'params_on_open';
    public const string REMOVE_PARAMS_ON_CLOSE = 'remove_params_on_close';

    public function dispatch(IComponent $modal, array $metadata = []): void;

    public function close(array $componentNames = []): void;

    public function jsonSerialize(): array;
}
