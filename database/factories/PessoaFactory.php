<?php

namespace Database\Factories;

use App\Models\Pessoa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pessoa>
 */
class PessoaFactory extends Factory
{
    protected $model = Pessoa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'cpf' => null,
            'rg' => null,
            'data_nascimento' => $this->faker->date(),
            'telefone_1' => $this->faker->numerify('###########'),
            'telefone_2' => null,
        ];
    }
}
