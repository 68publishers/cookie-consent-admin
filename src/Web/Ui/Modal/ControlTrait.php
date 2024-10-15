<?php

declare(strict_types=1);

namespace App\Web\Ui\Modal;

use App\Web\Ui\Modal\Dispatcher\ModalDispatcherInterface;
use Nette\Application\UI\Presenter;

/**
 * @method string getParameterId(string $name)
 */
trait ControlTrait
{
    private ModalDispatcherInterface $modalDispatcher;
    
    public function injectModalDispatcher(ModalDispatcherInterface $modalDispatcher): void
    {
        $this->modalDispatcher = $modalDispatcher;
    }

    public function handleOpenModal(string $modal): void
    {
        $modalControl = $this[$modal];

        $this->openModal($modalControl, [
            ModalDispatcherInterface::PARAMS_ON_OPEN => [
                Presenter::SignalKey => $this->getParameterId('openModal'),
                $this->getParameterId('modal') => $modal,
            ],
            ModalDispatcherInterface::REMOVE_PARAMS_ON_CLOSE => [
                Presenter::SignalKey,
                $this->getParameterId('modal') . '*',
            ],
        ], true);
    }

    protected function openModal(AbstractModalControl $control, array $metadata = [], bool $closePrevious = false): void
    {
        if ($closePrevious) {
            $this->closeModal();
        }

        $this->modalDispatcher->dispatch($control, $metadata);
    }

    protected function closeModal(?string $name = null): void
    {
        $this->modalDispatcher->close(null !== $name ? [$name] : []);
    }
}
