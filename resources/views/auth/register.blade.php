<x-imp-jquery />

@vite(['resources/js/cadUser.js', 'resources/js/formsWizard.js', 'resources/scss/_forms_wizard.scss'])
<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>
        <div class="m-4">
            <label class="step step0">Etapa Um</label>
            <label class="step step1" >Etapa Dois</label>
            <label class="step step2" >Etapa Três</label>
        </div>
        <x-validation-errors class="mb-4" />      
        
        <form class="employee-form mb-4" method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="form-section">
                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="mt-4">
                        <x-label for="terms">
                            <div class="flex items-center">
                                <x-checkbox name="terms" id="terms" required />

                                <div class="ms-2">
                                    {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </x-label>
                    </div>
                @endif
                <div class="mt-4"> 
                    <x-label for="name" value="{{ __('Nome*') }}" />
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name"  />
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email*') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                </div>

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Senha*') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" data-parsley-minlength="8" />
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" value="{{ __('Confirma Senha*') }}" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        {{ __('Já está registrado?') }}
                    </a>
                </div>
            </div>

            <div class="form-section">

                <div class="mt-4">
                    <x-label for="data_nascimento" value="Data Nascimento:*" />
                    <x-input id="data_nascimento" class="block mt-1 w-full" type="date" name="data_nascimento" :value="old('data_nascimento')" required autofocus autocomplete="data_nascimento" />
                </div>

                <div class="mt-4">
                    <x-label for="telefone_1" value="Telefone Principal:*" />
                    <x-input id="telefone_1" class="block mt-1 w-full" type="text" name="telefone_1" :value="old('telefone_1')" required autofocus autocomplete="telefone_1" maxlength="15"/>
                </div>

                <div class="mt-4">
                    <x-label for="telefone_2" value="Telefone Secundario: (Opcional)" />
                    <x-input id="telefone_2" class="block mt-1 w-full" type="text" name="telefone_2" :value="old('telefone_2')" autofocus autocomplete="telefone_2" maxlength="15"/>
                </div>

            </div>
            <div class="form-section">
                <div class="mt-4">
                    <x-label for="cpf" value="CPF: (Opcional)" />
                    <x-input id="cpf" class="block mt-1 w-full" type="text" name="cpf" :value="old('cpf')" autofocus autocomplete="cpf" maxlength="14"/>
                </div>

                <div class="mt-4">
                    <x-label for="rg" value="RG: (Opcional)" />
                    <x-input id="rg" class="block mt-1 w-full" type="text" name="rg" :value="old('rg')" autofocus autocomplete="rg" maxlength="12"/>
                </div>
            </div>
            <div class="form-navigation mt-3">
                <x-button type="button" class="arrow-left previous float-left">&lt; Previous</x-button>
                <x-button type="button" class="arrow-right next float-right">Next &gt;</x-button>   
                <x-button class="ms-4">
                    {{ __('Register') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
