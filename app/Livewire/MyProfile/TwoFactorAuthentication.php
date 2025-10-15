<?php

namespace App\Livewire\MyProfile;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Auth\MultiFactor\App\Actions\DisableAppAuthenticationAction;
use Filament\Auth\MultiFactor\App\Actions\RegenerateAppAuthenticationRecoveryCodesAction;
use Filament\Auth\MultiFactor\App\Actions\SetUpAppAuthenticationAction;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Writer;

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Schemas\Components as S;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
// use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorAuthentication extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    protected ?\App\Models\User $user;
    protected $recoveryCodeCount = 8;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->isEnabled();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Section::make('Multi-Factor Authentication')
                    ->description('Manage your multi-factor authentication settings. When multi-factor authentication is enabled, you will be prompted for a secure, random token during authentication.')
                    ->schema([
                        S\Flex::make([
                            $this->setupAction()
                                ->hidden(fn(): bool => $this->isEnabled()),
    
                            Action::make('verifyEmail')
                                ->label($this->user->hasVerifiedEmail() ? __('Email verified') : __('Verify Email'))
                                ->link()->icon($this->user->hasVerifiedEmail() ? Heroicon::EnvelopeOpen : Heroicon::Envelope)
                                ->modal()->color('teal')
                                ->action(function (): void {
                                    // Logic to send verification email
                                })
                                ->disabled($this->user->hasVerifiedEmail())
                                ->color($this->user->hasVerifiedEmail() ? 'gray' : 'primary')
                        ])
                        ->from('sm'),
                    ])
                    ->footer([])
                    ->aside()
                    ->compact()
            ]);
    }

    public function isEnabled(): bool
    {
        return filled(auth()->user()->getAppAuthenticationSecret());
    }

    public function render()
    {
        return view('livewire.my-profile.two-factor-authentication');
    }

    // Actions
    public function setupAction(): Action
    {
        $google2fa = new Google2FA();
        $user = auth()->user();
        $secretKey = $google2fa->generateSecretKey();

        $inlineQrCode = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->getAppAuthenticationHolderName(),
            $secretKey,
        );

        return Action::make('setUpAppAuthentication')
            ->label(__('filament-panels::auth/multi-factor/app/actions/set-up.label'))
            ->color('primary')
            ->icon(Heroicon::LockClosed)
            ->link()
            ->mountUsing(function (HasActions $livewire) use ($secretKey): void {
                $livewire->mergeMountedActionArguments([
                    'encrypted' => encrypt([
                        'secret' => $secretKey,
                        ...['recoveryCodes' => $this->generateRecoveryCodes()],
                        'userId' => auth()->id(),
                    ]),
                ]);
            })
            ->modalWidth(Width::Large)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalIcon(Heroicon::OutlinedLockClosed)
            ->modalIconColor('primary')
            ->modalHeading(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.heading'))
            ->modalDescription(new HtmlString(Blade::render(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.description'))))
            ->modifyWizardUsing(fn(S\Wizard $wizard) => $wizard->hiddenHeader())
            ->steps(fn(Action $action): array => [
                S\Wizard\Step::make('app')
                    ->schema([
                        S\Group::make([
                            S\Text::make(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.content.qr_code.instruction'))
                                ->color('neutral'),
                            S\Image::make(
                                url: fn(): string => $inlineQrCode,
                                alt: __('filament-panels::auth/multi-factor/app/actions/set-up.modal.content.qr_code.alt'),
                            )
                                ->imageHeight('12rem')
                                ->alignCenter(),
                            S\Flex::make([
                                S\Text::make(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.content.text_code.instruction'))
                                    ->color('neutral')
                                    ->grow(false),
                                S\Text::make(fn(): string => decrypt($action->getArguments()['encrypted'])['secret'])
                                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                                    ->color('neutral')
                                    ->copyable()
                                    ->copyMessage(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.content.text_code.messages.copied'))
                                    ->grow(false),
                            ])->from('sm'),
                        ])
                            ->dense(),
                        F\OneTimeCodeInput::make('code')
                            ->label(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.form.code.label'))
                            ->belowContent(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.form.code.below_content'))
                            ->validationAttribute(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.form.code.validation_attribute'))
                            ->rule(function (F\OneTimeCodeInput $input) use ($action, $google2fa): \Closure {
                                return function (string $attribute, $value, \Closure $fail) use ($action, $google2fa, $input): void {
                                    $secret = decrypt($action->getArguments()['encrypted'])['secret'] ?? auth()->user()->getAppAuthenticationSecret();
                                    $gCheck = $google2fa->verifyKey($secret, $value);
                                    if ($gCheck) return;
                                    $input->state(null);
                                    $input->getLivewire()->dispatch('reset-2fa-code-input');
                                    $fail(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.form.code.messages.invalid'));
                                };
                            })
                            ->extraAlpineAttributes(fn(F\OneTimeCodeInput $input) => [
                                'x-init' => "
                                    window.addEventListener('reset-2fa-code-input', e => {
                                        currentNumberOfDigits = null;
                                        const input = \$el.querySelector('.fi-one-time-code-input');
                                        input.value = null;
                                    })
                                ",
                            ])
                            ->required(),
                    ]),

                S\Wizard\Step::make('recovery')
                    ->schema([
                        S\Text::make(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.content.recovery_codes.instruction'))
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->color('neutral'),
                        S\UnorderedList::make(fn(): array => array_map(
                            fn(string $recoveryCode): S\Component => S\Text::make($recoveryCode)
                                ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                                ->size(\Filament\Support\Enums\TextSize::ExtraSmall)
                                ->color('neutral'),
                            decrypt($action->getArguments()['encrypted'] ?? encrypt([]))['recoveryCodes'] ?? [],
                        ))
                            ->size(\Filament\Support\Enums\TextSize::ExtraSmall),
                        S\Text::make(function () use ($action): Htmlable {
                            $recoveryCodes = decrypt($action->getArguments()['encrypted'])['recoveryCodes'];

                            return new HtmlString(
                                __('filament-panels::auth/multi-factor/recovery-codes-modal-content.actions.0') .
                                    ' ' .
                                    Action::make('copy')
                                    ->label(__('filament-panels::auth/multi-factor/recovery-codes-modal-content.actions.copy.label'))
                                    ->link()
                                    ->alpineClickHandler('
                                                    window.navigator.clipboard.writeText(' . \Illuminate\Support\Js::from(implode(PHP_EOL, $recoveryCodes)) . ')
                                                    $tooltip(' . \Illuminate\Support\Js::from(__('filament-panels::auth/multi-factor/recovery-codes-modal-content.messages.copied')) . ', {
                                                        theme: $store.theme,
                                                    })
                                                ')
                                    ->toHtml() .
                                    ' ' .
                                    __('filament-panels::auth/multi-factor/recovery-codes-modal-content.actions.1') .
                                    ' ' .
                                    Action::make('download')
                                    ->label(__('filament-panels::auth/multi-factor/recovery-codes-modal-content.actions.download.label'))
                                    ->link()
                                    ->url('data:application/octet-stream,' . urlencode(implode(PHP_EOL, $recoveryCodes)))
                                    ->extraAttributes(['download' => true])
                                    ->toHtml() .
                                    ' ' .
                                    __('filament-panels::auth/multi-factor/recovery-codes-modal-content.actions.2')
                            );
                        }),
                    ]),
            ])
            ->modalSubmitAction(fn(Action $action) => $action
                ->label(__('filament-panels::auth/multi-factor/app/actions/set-up.modal.actions.submit.label')))
            ->action(function (array $arguments): void {
                $encrypted = decrypt($arguments['encrypted']);
                $user = auth()->user();

                if ($user->getAuthIdentifier() !== $encrypted['userId']) {
                    // Avoid encrypted arguments being passed between users by verifying that the authenticated
                    // user is the same as the user that the encrypted arguments were issued for.
                    return;
                }

                DB::transaction(function () use ($encrypted, $user): void {
                    $user->saveAppAuthenticationSecret($encrypted['secret']);
                    $codes = $encrypted['recoveryCodes'];
                    if (!is_array($codes)) {
                        $user->saveAppAuthenticationRecoveryCodes(null);
                    } else {
                        $user->saveAppAuthenticationRecoveryCodes(array_map(
                            fn(string $code): string => Hash::make($code),
                            $codes,
                        ));
                    }
                });

                Notification::make()
                    ->title(__('filament-panels::auth/multi-factor/app/actions/set-up.notifications.enabled.title'))
                    ->success()
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->send();
            })
            ->rateLimit(5);
    }

    public function generateRecoveryCodes(): array
    {
        return \Illuminate\Support\Collection::times($this->recoveryCodeCount, fn(): string
        => \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10))->all();
    }


    public function verifyCode(string $code, ?string $secret = null): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $this->google2FA->verifyKey($secret ?? $user->getAppAuthenticationSecret(), $code, $this->getCodeWindow());
    }
}
