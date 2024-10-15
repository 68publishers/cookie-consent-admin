<?php

declare(strict_types=1);

namespace App\Web\AdminModule\UserModule\Control\UserForm;

use App\Application\Localization\ApplicationDateTimeZone;
use App\Application\Localization\Profiles;
use App\Domain\User\RolesEnum;
use App\ReadModel\Project\FindProjectSelectOptionsQuery;
use App\ReadModel\Project\ProjectSelectOptionView;
use App\ReadModel\User\UserView;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserCreatedEvent;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserFormProcessingFailedEvent;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserUpdatedEvent;
use App\Web\Ui\Control;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use App\Web\Utils\TranslatorUtils;
use Nepada\FormRenderer\TemplateRenderer;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\UserBundle\Domain\Command\CreateUserCommand;
use SixtyEightPublishers\UserBundle\Domain\Command\UpdateUserCommand;
use SixtyEightPublishers\UserBundle\Domain\Exception\EmailAddressUniquenessException;
use SixtyEightPublishers\UserBundle\Domain\Exception\UsernameUniquenessException;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;
use Throwable;

final class UserFormControl extends Control
{
    use FormFactoryOptionsTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Profiles $profiles,
        private readonly ?UserView $default = null,
    ) {}

    protected function createComponentForm(): Form
    {
        $form = $this->formFactory->create($this->getFormFactoryOptions());
        $translator = $this->getPrefixedTranslator();
        $renderer = $form->getRenderer();
        assert($renderer instanceof TemplateRenderer);

        $form->setTranslator($translator);
        $renderer->importTemplate(__DIR__ . '/templates/form.imports.latte');
        $renderer->getTemplate()->profiles = $this->profiles;

        $emailAddressField = $form->addText('email_address', 'email_address.field')
            ->setHtmlType('email')
            ->setRequired('email_address.required')
            ->addRule($form::Email, 'email_address.rule');

        $form->addText('firstname', 'firstname.field')
            ->setRequired('firstname.required');

        $form->addText('surname', 'surname.field')
            ->setRequired('surname.required');

        $profiles = [];
        foreach ($this->profiles->all() as $profile) {
            $profiles[$profile->locale()] = $profile->name();
        }

        $form->addSelect('profile', 'profile.field', $profiles)
            ->setRequired('profile.required')
            ->setTranslator(null)
            ->setDefaultValue($this->profiles->active()->locale());

        $form->addSelect('timezone', 'timezone.field')
            ->setItems(ApplicationDateTimeZone::all(), false)
            ->setRequired('timezone.required')
            ->setTranslator(null)
            ->setDefaultValue(ApplicationDateTimeZone::get()->getName())
            ->setOption('searchbar', true);

        $form->addCheckboxList('roles', 'roles.field', array_combine(RolesEnum::values(), TranslatorUtils::translateArray($translator, '//layout.role_name.', RolesEnum::values())))
            ->setTranslator(null)
            ->setRequired('roles.required');

        $form->addPassword('password', 'password.field')
            ->setOption('description', $this->resolvePasswordDescription(null !== $this->default, null !== $this->default && null !== $this->default->password));

        $form->addMultiSelect('projects', 'projects.field', $this->getProjectOptions())
            ->checkDefaultValue(false)
            ->setTranslator(null)
            ->setOption('tags', true)
            ->setOption('searchbar', true);

        $form->addProtection('//layout.form_protection');

        $form->addSubmit('save', null === $this->default ? 'save.field' : 'update.field');

        if (null !== $this->default) {
            $emailAddressField->setRequired(false)
                ->setDisabled();

            $form->setDefaults([
                'email_address' => $this->default->emailAddress->value(),
                'firstname' => $this->default->name->firstname(),
                'surname' => $this->default->name->surname(),
                'profile' => $this->default->profileLocale->value(),
                'timezone' => $this->default->timezone->getName(),
                'roles' => $this->default->roles->toArray(),
                'projects' => array_map(
                    static fn (ProjectSelectOptionView $view): string => $view->id->toString(),
                    $this->queryBus->dispatch(FindProjectSelectOptionsQuery::byUser($this->default->id->toString())),
                ),
            ]);
        }

        $form->onSuccess[] = function (Form $form): void {
            $this->saveUser($form);
        };

        return $form;
    }

    private function saveUser(Form $form): void
    {
        $values = $form->values;

        if (null === $this->default) {
            $userId = UserId::new();
            $command = CreateUserCommand::create(
                $values->email_address,
                '' === $values->password ? null : $values->password,
                $values->email_address,
                $values->firstname,
                $values->surname,
                $values->roles,
                $userId->toString(),
            );
        } else {
            $userId = $this->default->id;
            $command = UpdateUserCommand::create($userId->toString())
                ->withFirstname($values->firstname)
                ->withSurname($values->surname)
                ->withRoles($values->roles);

            if ('' !== $values->password) {
                $command = $command->withPassword($values->password);
            }
        }

        $command = $command
            ->withParam('profile', $values->profile)
            ->withParam('timezone', $values->timezone)
            ->withParam('project_ids', $values->projects);

        try {
            $this->commandBus->dispatch($command);
            $form['password']->setOption('description', $this->resolvePasswordDescription(true, '' !== $values->password || (null !== $this->default && null !== $this->default->password)));
        } catch (UsernameUniquenessException|EmailAddressUniquenessException $e) {
            $emailAddressField = $form->getComponent('email_address');
            assert($emailAddressField instanceof TextInput);

            $emailAddressField->addError('email_address.error.duplicated_value');

            return;
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->dispatchEvent(new UserFormProcessingFailedEvent($e));

            return;
        }

        $this->dispatchEvent(null === $this->default ? new UserCreatedEvent($userId) : new UserUpdatedEvent($userId));
        $this->redrawControl();
    }

    private function getProjectOptions(): array
    {
        $options = [];

        /** @var ProjectSelectOptionView $projectSelectOptionView */
        foreach ($this->queryBus->dispatch(FindProjectSelectOptionsQuery::all()) as $projectSelectOptionView) {
            $options += $projectSelectOptionView->toOption();
        }

        return $options;
    }

    private function resolvePasswordDescription(bool $exists, bool $hasPassword): string
    {
        return !$exists ? 'password.description.create_user' : ('password.description.update_user.' . (!$hasPassword ? 'create_password': 'change_password'));
    }
}
