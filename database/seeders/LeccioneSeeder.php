<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Leccion;


class LeccioneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Leccion::insert([
            //lecciones academicas
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 1',
                'hora_inicio' => '07:00',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '07:40',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 2',
                'hora_inicio' => '07:40',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '08:20',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 3',
                'hora_inicio' => '08:20',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '09:00',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 4',
                'hora_inicio' => '09:20',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '10:00',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 5',
                'hora_inicio' => '10:00',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '10:40',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 6',
                'hora_inicio' => '10:40',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '11:20',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 7',
                'hora_inicio' => '12:05',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '12:45',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 8',
                'hora_inicio' => '12:45',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '01:25',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Leccin 9',
                'hora_inicio' => '01:25',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '02:05',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 10',
                'hora_inicio' => '02:20',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '03:00',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 11',
                'hora_inicio' => '03:00',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '03:40',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Academica',
                'leccion' => 'Lección 12',
                'hora_inicio' => '03:40',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '04:20',
                'hora_final_periodo' => 'PM',
            ],

            //lecciones tecnicas
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Tcnica 1',
                'hora_inicio' => '07:00',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '08:00',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 2',
                'hora_inicio' => '08:00',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '09:00',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 3',
                'hora_inicio' => '09:20',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '10:20',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 4',
                'hora_inicio' => '10:20',
                'hora_inicio_periodo' => 'AM',
                'hora_final' => '11:20',
                'hora_final_periodo' => 'AM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 5',
                'hora_inicio' => '12:05',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '01:05',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 6',
                'hora_inicio' => '01:05',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '02:05',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 7',
                'hora_inicio' => '02:20',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '03:20',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección Técnica 8',
                'hora_inicio' => '03:20',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '04:20',
                'hora_final_periodo' => 'PM',
            ],

            //lecciones tecnicas nocturnos
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección 1',
                'hora_inicio' => '05:50',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '06:35',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección 2',
                'hora_inicio' => '06:35',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '07:20',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección 3',
                'hora_inicio' => '07:40',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '08:25',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección 4',
                'hora_inicio' => '08:25',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '09:10',
                'hora_final_periodo' => 'PM',
            ],
            [
                'tipoLeccion' => 'Tecnica',
                'leccion' => 'Lección 5',
                'hora_inicio' => '09:10',
                'hora_inicio_periodo' => 'PM',
                'hora_final' => '09:55',
                'hora_final_periodo' => 'PM',
            ],
        ]);
    }
}
