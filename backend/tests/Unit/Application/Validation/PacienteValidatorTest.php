<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\Validation;

use CodigoAzul\Application\Validation\PacienteValidator;
use CodigoAzul\Domain\Model\Paciente;
use CodigoAzul\Tests\Fakes\FakePacienteRepository;
use PHPUnit\Framework\TestCase;

final class PacienteValidatorTest extends TestCase
{
    public function test_dni_con_letras_es_invalido(): void
    {
        $validador = new PacienteValidator(new FakePacienteRepository());

        $errores = $validador->validar('Juan Pérez', '30ABC456', '1990-01-01', '1', null);

        self::assertContains('El DNI debe tener entre 7 y 8 dígitos numéricos.', $errores);
    }

    public function test_fecha_de_nacimiento_futura_es_invalida(): void
    {
        $validador = new PacienteValidator(new FakePacienteRepository());
        $manana = date('Y-m-d', strtotime('+1 day'));

        $errores = $validador->validar('Juan Pérez', '30123456', $manana, '1', null);

        self::assertContains('La fecha de nacimiento no puede ser futura.', $errores);
    }

    /**
     * Regresion directa del bug encontrado en el prototipo de front-end:
     * el validador de pacientes no comprobaba DNI duplicado (a diferencia
     * de área/usuario, que sí lo hacían), permitiendo dos fichas con el
     * mismo documento.
     */
    public function test_no_permite_dni_duplicado(): void
    {
        $repositorio = new FakePacienteRepository();
        $repositorio->guardar(new Paciente(null, 'Paciente Uno', '30123456', '1990-01-01', '', 1));
        $validador = new PacienteValidator($repositorio);

        $errores = $validador->validar('Paciente Dos', '30123456', '1990-01-01', '1', null);

        self::assertContains('Ya existe un paciente registrado con ese DNI.', $errores);
    }

    public function test_permite_conservar_el_propio_dni_al_editar(): void
    {
        $repositorio = new FakePacienteRepository();
        $paciente = $repositorio->guardar(new Paciente(null, 'Paciente Uno', '30123456', '1990-01-01', '', 1));
        $validador = new PacienteValidator($repositorio);

        $errores = $validador->validar('Paciente Uno', '30123456', '1990-01-01', '1', $paciente->id());

        self::assertSame([], $errores);
    }

    public function test_sin_area_es_invalido(): void
    {
        $validador = new PacienteValidator(new FakePacienteRepository());

        $errores = $validador->validar('Juan Pérez', '30123456', '1990-01-01', null, null);

        self::assertContains('Debe asignar un área al paciente.', $errores);
    }
}
