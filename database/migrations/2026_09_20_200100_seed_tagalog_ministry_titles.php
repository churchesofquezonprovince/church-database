<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $books = [
            'CL' => 'Ang Buhay-Ekklesia',
            'KT' => 'Pagkaalam ng Katotohanan',
            'SL' => 'Espiritu at Buhay',
            'TO' => 'Magtiwala at Sumunod',
            'AB' => 'Pagkatapos Maligtas',
            'HG' => 'Ang Mataas na Ebanghelyo',
        ];

        $lessons = [
            // =================================================
            // CL — Ang Buhay-Ekklesia
            // =================================================
            'CL01' => 'Ang Dakilang Hiwaga—si Kristo at ang Ekklesia',
            'CL02' => 'Ang Dalawang Aspekto ng Ekklesia',
            'CL03' => 'Ang Pagbabawi ng Panginoon',
            'CL04' => 'Ano nga ba Tayo',
            'CL05' => 'Pagkilala sa mga Sekta',
            'CL06' => 'Paglilingkod sa Panginoon',
            'CL07' => 'Namumukod-tanging Nabubuhay para sa Ebanghelyo',
            'CL08' => 'Ang Pulso ng Buhay sa Pagsasagawa ng Bagong Daan—Ang Tahanan',
            'CL09' => 'Pagpapastol sa mga Tupa ng Panginoon',
            'CL10' => 'Ang mga Buháy na Grupo',
            'CL11' => 'Ang mga Panggrupong Pagpupulong',
            'CL12' => 'Ang Pagpupulong ng Pagpipira-piraso ng Tinapay',
            'CL13' => 'Pagpupulong ng Pagpopropesiya',
            'CL14' => 'Ang Paghahandog ng mga Materyal na Yaman',
            'CL15' => 'Ang Paghahalo ng Katawan ni Kristo',
            'CL16' => 'Ang Pagtatayo ng Katawan ni Kristo',

            // =================================================
            // KT — Pagkaalam ng Katotohanan
            // =================================================
            'KT01' => 'Ang Naprosesong Tres-unong Diyos',
            'KT02' => 'Ang Nagpapaloob ng Lahat na Kristo',
            'KT03' => 'Ang Napasukdol na Espiritu',
            'KT04' => 'Ang Pantaong Espiritu',
            'KT05' => 'Ang Dibinong Buhay na Walang-hanggan',
            'KT06' => 'Ang Tres-unong Diyos bilang Buhay na Tumitigmak sa Taong May Tatlong Bahagi',
            'KT07' => 'Ang Ekklesia',
            'KT08' => 'Ang Tatlong Aspekto ng Kaharian ng Kalangitan',
            'KT09' => 'Ang Ikalawang Pagdating ni Kristo',
            'KT10' => 'Ang Bagong Herusalem',
            'KT11' => 'Ang mga Paksa ng mga Aklat sa Lumang Tipan',
            'KT12' => 'Ang mga Paksa ng mga Aklat sa Bagong Tipan',
            'KT13' => 'Ang Salin sa Pagbabawi ng Biblia',
            'KT14' => 'Pagkaalam ng mga Himno',
            'KT15' => 'Ang mga Pag-aaral Pambuhay',
            'KT16' => 'Ang Banal na Salita para sa Pang-umagang Pagpapanauli',

            // =================================================
            // SL — Espiritu at Buhay
            // =================================================
            'SL01' => 'Panalangin',
            'SL02' => 'Pagbasa ng Biblia',
            'SL03' => 'Mga Espiritwal na Kasama',
            'SL04' => 'Ang Pag-eensayo ng Espiritu',
            'SL05' => 'Pag-awit ng Himno',
            'SL06' => 'Pagpuri',
            'SL07' => 'Ang Pandama ng Buhay',
            'SL08' => 'Ang Salamuha ng Buhay',
            'SL09' => 'Kaisang Espiritu ng Panginoon',
            'SL10' => 'Paglakad Ayon sa Espiritu',
            'SL11' => 'Pagkasilang na Muli',
            'SL12' => 'Pagpapabanal',
            'SL13' => 'Pagpapabago',
            'SL14' => 'Transpormasyon',
            'SL15' => 'Pagwawangis',
            'SL16' => 'Pagluluwalhati',

            // =================================================
            // TO — Magtiwala at Sumunod
            // =================================================
            'TO01' => 'Sabihin sa Kanya',
            'TO02' => 'Paglagak ng Ating Kabalisahan sa Diyos',
            'TO03' => 'Ang Katapusan ng Tao ay Simula ng Diyos',
            'TO04' => 'Bakit Nagdurusa ang mga Mananampalataya',
            'TO05' => 'Ginagamit ng Diyos ang Kapaligiran para sa Ikabubuti ng mga Mananampalataya',
            'TO06' => 'At kay Pedro',
            'TO07' => 'Ang Kayamanan sa mga Sisidlang Lupa',
            'TO08' => 'Binusog Niya ang Nagugutom ng Mabubuting Bagay',
            'TO09' => 'Pagtatamasa kay Kristo',
            'TO10' => 'Nilalabanan ang Diyablo',
            'TO11' => 'Katunayan, Pananampalataya, at Karanasan',
            'TO12' => 'Pananampalataya at Pagtalima',
            'TO13' => 'Pinahahalagahan ang Panginoong Hesus',
            'TO14' => 'Huwag Ibigin ang Sanlibutan',
            'TO15' => 'Ang Nag-iingat na Kapangyarihan ng Diyos',
            'TO16' => 'Ang Pag-asa ng Buhay-Kristiyano',

            // =================================================
            // AB — Pagkatapos Maligtas
            // =================================================
            'AB01' => 'Ang Katiyakan ng Kaligtasan',
            'AB02' => 'Paglilinis ng Lumang Pamumuhay',
            'AB03' => 'Pang-umagang Pagpapanauli',
            'AB04' => 'Ang Pinaghalong Espiritu',
            'AB05' => 'Pagtawag sa Pangalan ng Panginoon',
            'AB06' => 'Ang Pagpupuspos ng Espiritu',
            'AB07' => 'Mga Salita ng Buhay',
            'AB08' => 'Pagbabasa-Dalangin ng Salita ng Diyos',
            'AB09' => 'Ang Ekonomiya ng Diyos',
            'AB10' => 'Pag-aalay',
            'AB11' => 'Ang Katawan ni Kristo',
            'AB12' => 'Ang Buhay-Pagpupulong',
            'AB13' => 'Ang Ministeryo ng Bagong Tipan',
            'AB14' => 'Ang Itinalagang Daan ng Diyos',
            'AB15' => 'Pambatas na Pagtutubos',
            'AB16' => 'Organikong Pagliligtas',

            // =================================================
            // HG — Ang Mataas na Ebanghelyo
            // =================================================
            'HG01' => 'Ang Hiwaga ng Pantaong Buhay',
            'HG02' => 'Si Kristo bilang Kahulugan ng Pantaong Buhay',
            'HG03' => 'Ang Buhay-Ekklesia bilang Tunay na Buhay-Komunidad',
            'HG04' => 'Ang Biblia',
            'HG05' => 'Mayroong Diyos',
            'HG06' => 'Si Kristo ay Diyos',
            'HG07' => 'Si Kristo ay Espiritu',
            'HG08' => 'Si Kristo ay Buhay',
            'HG09' => 'Ang Pagtutubos ni Kristo',
            'HG10' => 'Ang Pagliligtas ni Kristo',
            'HG11' => 'Buhay sa pamamagitan ng Pananampalataya',
            'HG12' => 'Ang Mapagmahal na Ama',
            'HG13' => 'Si Hesus bilang Kaibigan ng mga Makasalanan',
            'HG14' => 'Pagsisisi at Pagpapahayag',
            'HG15' => 'Bautismo',
            'HG16' => 'Pagharap sa Pag-uusig',
        ];

        /*
         * Safety check:
         * do not partially seed if our expected catalog codes
         * are not present in the current database.
         */
        $existingBookCodes = DB::table('ministry_books')
            ->whereIn('code', array_keys($books))
            ->pluck('code')
            ->all();

        $missingBooks = array_diff(
            array_keys($books),
            $existingBookCodes
        );

        if ($missingBooks !== []) {
            throw new RuntimeException(
                'Missing Ministry Book codes: '
                . implode(', ', $missingBooks)
            );
        }

        $existingLessonCodes = DB::table('ministry_lessons')
            ->whereIn('code', array_keys($lessons))
            ->pluck('code')
            ->all();

        $missingLessons = array_diff(
            array_keys($lessons),
            $existingLessonCodes
        );

        if ($missingLessons !== []) {
            throw new RuntimeException(
                'Missing Ministry Lesson codes: '
                . implode(', ', $missingLessons)
            );
        }

        DB::transaction(
            function () use ($books, $lessons): void {
                foreach ($books as $code => $title) {
                    DB::table('ministry_books')
                        ->where('code', $code)
                        ->update([
                            'title_tagalog' => $title,
                            'updated_at' => now(),
                        ]);
                }

                foreach ($lessons as $code => $title) {
                    DB::table('ministry_lessons')
                        ->where('code', $code)
                        ->update([
                            'title_tagalog' => $title,
                            'updated_at' => now(),
                        ]);
                }
            }
        );
    }

    public function down(): void
    {
        $books = [
            'CL' => 'Ang Buhay-Ekklesia',
            'KT' => 'Pagkaalam ng Katotohanan',
            'SL' => 'Espiritu at Buhay',
            'TO' => 'Magtiwala at Sumunod',
            'AB' => 'Pagkatapos Maligtas',
            'HG' => 'Ang Mataas na Ebanghelyo',
        ];

        $lessons = [
            'CL01' => 'Ang Dakilang Hiwaga—si Kristo at ang Ekklesia',
            'CL02' => 'Ang Dalawang Aspekto ng Ekklesia',
            'CL03' => 'Ang Pagbabawi ng Panginoon',
            'CL04' => 'Ano nga ba Tayo',
            'CL05' => 'Pagkilala sa mga Sekta',
            'CL06' => 'Paglilingkod sa Panginoon',
            'CL07' => 'Namumukod-tanging Nabubuhay para sa Ebanghelyo',
            'CL08' => 'Ang Pulso ng Buhay sa Pagsasagawa ng Bagong Daan—Ang Tahanan',
            'CL09' => 'Pagpapastol sa mga Tupa ng Panginoon',
            'CL10' => 'Ang mga Buháy na Grupo',
            'CL11' => 'Ang mga Panggrupong Pagpupulong',
            'CL12' => 'Ang Pagpupulong ng Pagpipira-piraso ng Tinapay',
            'CL13' => 'Pagpupulong ng Pagpopropesiya',
            'CL14' => 'Ang Paghahandog ng mga Materyal na Yaman',
            'CL15' => 'Ang Paghahalo ng Katawan ni Kristo',
            'CL16' => 'Ang Pagtatayo ng Katawan ni Kristo',

            'KT01' => 'Ang Naprosesong Tres-unong Diyos',
            'KT02' => 'Ang Nagpapaloob ng Lahat na Kristo',
            'KT03' => 'Ang Napasukdol na Espiritu',
            'KT04' => 'Ang Pantaong Espiritu',
            'KT05' => 'Ang Dibinong Buhay na Walang-hanggan',
            'KT06' => 'Ang Tres-unong Diyos bilang Buhay na Tumitigmak sa Taong May Tatlong Bahagi',
            'KT07' => 'Ang Ekklesia',
            'KT08' => 'Ang Tatlong Aspekto ng Kaharian ng Kalangitan',
            'KT09' => 'Ang Ikalawang Pagdating ni Kristo',
            'KT10' => 'Ang Bagong Herusalem',
            'KT11' => 'Ang mga Paksa ng mga Aklat sa Lumang Tipan',
            'KT12' => 'Ang mga Paksa ng mga Aklat sa Bagong Tipan',
            'KT13' => 'Ang Salin sa Pagbabawi ng Biblia',
            'KT14' => 'Pagkaalam ng mga Himno',
            'KT15' => 'Ang mga Pag-aaral Pambuhay',
            'KT16' => 'Ang Banal na Salita para sa Pang-umagang Pagpapanauli',

            'SL01' => 'Panalangin',
            'SL02' => 'Pagbasa ng Biblia',
            'SL03' => 'Mga Espiritwal na Kasama',
            'SL04' => 'Ang Pag-eensayo ng Espiritu',
            'SL05' => 'Pag-awit ng Himno',
            'SL06' => 'Pagpuri',
            'SL07' => 'Ang Pandama ng Buhay',
            'SL08' => 'Ang Salamuha ng Buhay',
            'SL09' => 'Kaisang Espiritu ng Panginoon',
            'SL10' => 'Paglakad Ayon sa Espiritu',
            'SL11' => 'Pagkasilang na Muli',
            'SL12' => 'Pagpapabanal',
            'SL13' => 'Pagpapabago',
            'SL14' => 'Transpormasyon',
            'SL15' => 'Pagwawangis',
            'SL16' => 'Pagluluwalhati',

            'TO01' => 'Sabihin sa Kanya',
            'TO02' => 'Paglagak ng Ating Kabalisahan sa Diyos',
            'TO03' => 'Ang Katapusan ng Tao ay Simula ng Diyos',
            'TO04' => 'Bakit Nagdurusa ang mga Mananampalataya',
            'TO05' => 'Ginagamit ng Diyos ang Kapaligiran para sa Ikabubuti ng mga Mananampalataya',
            'TO06' => 'At kay Pedro',
            'TO07' => 'Ang Kayamanan sa mga Sisidlang Lupa',
            'TO08' => 'Binusog Niya ang Nagugutom ng Mabubuting Bagay',
            'TO09' => 'Pagtatamasa kay Kristo',
            'TO10' => 'Nilalabanan ang Diyablo',
            'TO11' => 'Katunayan, Pananampalataya, at Karanasan',
            'TO12' => 'Pananampalataya at Pagtalima',
            'TO13' => 'Pinahahalagahan ang Panginoong Hesus',
            'TO14' => 'Huwag Ibigin ang Sanlibutan',
            'TO15' => 'Ang Nag-iingat na Kapangyarihan ng Diyos',
            'TO16' => 'Ang Pag-asa ng Buhay-Kristiyano',

            'AB01' => 'Ang Katiyakan ng Kaligtasan',
            'AB02' => 'Paglilinis ng Lumang Pamumuhay',
            'AB03' => 'Pang-umagang Pagpapanauli',
            'AB04' => 'Ang Pinaghalong Espiritu',
            'AB05' => 'Pagtawag sa Pangalan ng Panginoon',
            'AB06' => 'Ang Pagpupuspos ng Espiritu',
            'AB07' => 'Mga Salita ng Buhay',
            'AB08' => 'Pagbabasa-Dalangin ng Salita ng Diyos',
            'AB09' => 'Ang Ekonomiya ng Diyos',
            'AB10' => 'Pag-aalay',
            'AB11' => 'Ang Katawan ni Kristo',
            'AB12' => 'Ang Buhay-Pagpupulong',
            'AB13' => 'Ang Ministeryo ng Bagong Tipan',
            'AB14' => 'Ang Itinalagang Daan ng Diyos',
            'AB15' => 'Pambatas na Pagtutubos',
            'AB16' => 'Organikong Pagliligtas',

            'HG01' => 'Ang Hiwaga ng Pantaong Buhay',
            'HG02' => 'Si Kristo bilang Kahulugan ng Pantaong Buhay',
            'HG03' => 'Ang Buhay-Ekklesia bilang Tunay na Buhay-Komunidad',
            'HG04' => 'Ang Biblia',
            'HG05' => 'Mayroong Diyos',
            'HG06' => 'Si Kristo ay Diyos',
            'HG07' => 'Si Kristo ay Espiritu',
            'HG08' => 'Si Kristo ay Buhay',
            'HG09' => 'Ang Pagtutubos ni Kristo',
            'HG10' => 'Ang Pagliligtas ni Kristo',
            'HG11' => 'Buhay sa pamamagitan ng Pananampalataya',
            'HG12' => 'Ang Mapagmahal na Ama',
            'HG13' => 'Si Hesus bilang Kaibigan ng mga Makasalanan',
            'HG14' => 'Pagsisisi at Pagpapahayag',
            'HG15' => 'Bautismo',
            'HG16' => 'Pagharap sa Pag-uusig',
        ];

        DB::transaction(
            function () use ($books, $lessons): void {
                foreach ($books as $code => $title) {
                    DB::table('ministry_books')
                        ->where('code', $code)
                        ->where(
                            'title_tagalog',
                            $title
                        )
                        ->update([
                            'title_tagalog' => null,
                            'updated_at' => now(),
                        ]);
                }

                foreach ($lessons as $code => $title) {
                    DB::table('ministry_lessons')
                        ->where('code', $code)
                        ->where(
                            'title_tagalog',
                            $title
                        )
                        ->update([
                            'title_tagalog' => null,
                            'updated_at' => now(),
                        ]);
                }
            }
        );
    }
};
