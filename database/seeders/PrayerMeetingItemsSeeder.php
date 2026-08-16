<?php

namespace Database\Seeders;

use App\Models\AttendanceSheet;
use App\Models\PrayerMeetingItem;
use App\Models\PrayerMeetingItemLine;
use Illuminate\Database\Seeder;

class PrayerMeetingItemsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLucena();
        $this->seedLucban();
    }

    private function seedLucena(): void
    {
        $sheet = $this->prayerSheetFor([
            'Lucena City',
            'Lucena',
        ]);

        $item = $this->itemFor(
            $sheet,
            $sheet?->locality ?: 'Lucena City',
            'Prayer Meeting Items',
            '2026-07-21'
        );

        $this->replaceLines($item, [
            ['roman', 'I.', 'Work'],

            ['letter', 'A.', 'The coming August 2026 term of FTTMa, pray for all the applicants and their preparation and need in the coming fulltime training.'],

            ['letter', 'C.', '2026 Summer International College Training in Asia: Yong-in City, South Korea, July 20 to 26, 2026. Pray for 120 participants from Phils.'],

            ['letter', 'D.', 'ITERO Video Training: October 1- 11, 2026.'],

            ['letter', 'E.', 'Fellowship Among the Churches: Oct. 16-18, 2026'],

            ['letter', 'F.', 'PTERO Venue: Malabon Training Center, Oct. 23-25, 2026'],

            ['roman', 'II.', 'PROVINCE'],

            ['letter', 'A.', 'The newly baptized ones and the home meetings opened last long propagation (May 25 to July 4, 2026) be continually taken care of. More shepherds be raised up to continue the work of shepherding among them.'],

            ['letter', 'B.', 'Goal for 2026: to recover the Lords Table Meeting in Mulanay, Macalelon and Candelaria; for 2027 – to establish the testimony in Alabat. For the direction of the Work to be fulfilled in all the churches: Closely following the ministry, practicing the PSRP and laboring in the Word.'],

            ['letter', 'C.', 'Pray for the present and incoming trainees: Hannah Leah Cesar, John David Liwag, Hiram Chalcedony Guyo, Timothy Cesar, and the incoming FT1 brothers: Hesron Cuaton and Gerald Lopez.'],

            ['letter', 'D.', 'SemiAnnual Training: The Believers (1) Video Training of BaRZonMiMaRoPa: July 21,24, 27, 28, 31, August 3, 4 and 7, 2026, every 7pm to 9pm.
*Aug.8 - BLENDING'],

            ['letter', 'E.', 'Churching and Blending to Davao: August 19 to 23, 2026.'],

            ['letter', 'F.', 'The practice of the God ordained way and the practice of PSRP among the saints and the churches be prevailing.'],

            ['letter', 'G.', 'Campus Work'],
            ['number', '1.', 'CEFI Campus:'],
            ['number', '2.', 'SLSU Lucban:'],
            ['number', '3.', 'Ilayang Dupay Extension High School'],
            ['number', '4.', 'DLL (Dalubhasaan Lungsod ng Lucena)'],
            ['number', '5.', 'MSEUF, may campus meeting be opened for this coming Academic Year.'],

            ['letter', 'H.', 'Childrens Meeting in every localities of the province be prevailing: Lucena, Tayabas, Pagbilao, Atimonan, Lucban, Plaridel, Gumaca, Catanauan, Mulanay'],

            ['roman', 'III.', 'OTHERS:'],

            ['bullet', '*', 'Pray for other meetings of the churches:
Service Meeting – Lords Day after dinner
Brothers Meeting – every other Saturday
Sisters Meeting – July 18
Special Meeting – July 25
Shepherds Meeting – last Saturday afternoon of the month
Young Adults Meeting -
Young Peoples Meeting – Lords Day, 1:30 pm
Prayer Meeting – every Tuesdays'],

            ['bullet', '*', 'Construction of Meeting Halls:'],
            ['number', '1.', 'Pray for the focus in Region 4: the ongoing construction of the Student Center and Meeting hall of the church in Victoria, Oriental Mindoro.'],
            ['number', '2.', 'The continuation of the construction of the Blending Hall in Lucena City.'],
            ['number', '3.', 'The completion of the construction of the meeting hall in Catanauan, Quezon.'],

            ['bullet', '*', 'Pray for the health of all the saints.'],
        ]);
    }

    private function seedLucban(): void
    {
        $sheet = $this->prayerSheetFor([
            'Lucban',
            'Lucban City',
        ]);

        $item = $this->itemFor(
            $sheet,
            $sheet?->locality ?: 'Lucban',
            'Prayer Meeting Items',
            '2026-07-21'
        );

        $this->replaceLines($item, [
            ['roman', 'I.', 'Ekklesia sa Lucban'],

            ['letter', 'A.', 'Mapalakas ang mga kapatid na lalaki sa kanilang panloob na tao para sa pangunguna sa mga banal sa paghahabol sa Panginoon.
Roger Deapera, Raymund Deapera, Virgilio Abcede, Hermogenes Salazar, Kristian Jay Oblefias, Benjamin Hombrebueno, Jovic Liwag, Hubert Hernandez'],

            ['letter', 'B.', 'Pagkakaroon ng palagiang pag-aalay ng sarili at pagbibigay ng puso sa pakikilahok sa panalangin, paghayo, at paghanandong ng materyal na yaman para sa gawain ng Panginoon.
Cherry Deapera, Elizabeth Deapera, Sherlyn Hernandez, Charina Salazar, Pamela Oblefias, MJ Roxas, Angel Hufana, Mary Grace Ramos'],

            ['letter', 'C.', 'Mapreserba at manatili sa buhay-ekklesia ang mga kabataang sina:
Brothers: Justine Laguidao, Mark Jireh Deapera, Jeremy & Jerry Abcede, Jaymark, Mark Rigel Ibardelosa, Rayven Salvatierra, Mel
Sisters: Kyle Laguidao, Rica & Sairyl Deapera, Althea May Ibardelosa, Audrey Anne, Fiona Zayrel Talpe, Irish Ann Salvatierra, Kim Joy Abuyan
Student Center Dwellers: Carlo Berunia, Ram Suarez, Alexander Arcega, Mitchelle Lei Martinez, Daniella Reforma, Mica Lagrazon, Jhoanna Rose Javier, Jhunelat Adan, Claire Manalastas, Jana Arcueno, Mariel Manimtim, Kyla Aguila, at Eumi Manalastas'],

            ['letter', 'D.', 'Mapalakas at madala sa buhay-ekklesia ang mga nabautismohang kapatid:
Brothers: Mark John Esclanda, Rino Villar, Jhorielle Jardin, Jhon Michael Cosejo, Yohan Sebastian Gaelo, Yancy Jaedon Gaelo, Ronnie Carneo, Ronaldo Flores, Michael Laguador, Celso Pabico
Sisters: Joy Villa, Teresa Ladesma, Armie Nanadiego, Ria Mae Padilla, Jhoey Nhicole Obania, Yuna Kariza Gaelo, Rhea Veluz, Claudette Raton, Arlene Palencia, Jennelyn Villaverde, Nenita Villaverde, Rosalinda Flores, Rosalyn Flores, Marian Zarsuela
Family:
Pavino Family: Fernando, Jocelyn, Billmark, Frederict, Cristalyn, Ronald, Rosemarie at Precious Rose.'],

            ['letter', 'E.', 'Sambahayan kaligtasan para sa'],

            ['plain', null, 'Ibardelosa Family:
Rodolfo & Mylene, Cedric, Cherry, Mark Rigel, Thea.
Cleo May & Justine, Jaiden Avery Lacsamana'],

            ['plain', null, 'Oblefias Family:
Vinzon and Nelda'],

            ['plain', null, 'Gomo Family:
Yolanda, Peter, & Patrick'],

            ['plain', null, 'Carneo Family:
Ronelyen, Princess, Michelle, Arnel, Rosalyn'],

            ['plain', null, 'Abog Family:
Jude, Adrian, Andrea, Maria Briella'],

            ['plain', null, 'Pabico Family:
Donnalyn, Christian, Donnamae, Denis'],

            ['plain', null, 'Sanchez Family:
Renate, Ria, Rhejene, Rhea, Cassie, Rhalf'],

            ['roman', 'II.', 'Iba pa:'],

            ['bullet', '-', 'Goal for 2026: Recovery of LTM in Mulanay, Macalelon, and Candelaria'],

            ['bullet', '-', 'Goal for 2027: Establishment of the testimony of Jesus in Alabat'],

            ['bullet', '-', 'The coming 80th term of FTTMa, pray for all the applicants and their preparation and need in the coming fulltime training. Incoming Trainee for 80th term of FTTMa: Brother Hesron Cuaton of Padre Burgos.'],

            ['bullet', '-', '2026 Summer International College Training in Asia: Yong-in City, South Korea, July 20 to 26, 2026.'],

            ['bullet', '-', 'ITERO Video Training: October 1- 11, 2026.'],

            ['bullet', '-', 'Fellowship Among the Churches : Oct. 16-18, 2026'],

            ['bullet', '-', 'PTERO: Malabon Training Center, Oct. 23-25, 2026'],

            ['bullet', '-', 'SemiAnnual Training: The Believers (1) Video Training of BaRZonMiMaRoPa: July 13 to August 7, 2026.'],

            ['bullet', '-', 'Churching and Blending to Davao: August 19 to 23, 2026.'],

            ['bullet', '-', 'The practice of the God ordained way and the practice of PSRP among the saints and the churches be prevailing.'],
        ]);
    }

    private function prayerSheetFor(array $localities): ?AttendanceSheet
    {
        foreach ($localities as $locality) {
            $sheet = AttendanceSheet::query()
                ->where('sheet_type', AttendanceSheet::TYPE_PRAYER_MEETING)
                ->whereRaw('LOWER(locality) = ?', [
                    mb_strtolower($locality),
                ])
                ->first();

            if ($sheet) {
                return $sheet;
            }
        }

        return null;
    }

    private function itemFor(
        ?AttendanceSheet $sheet,
        string $locality,
        string $title,
        string $meetingDate
    ): PrayerMeetingItem {
        return PrayerMeetingItem::query()
            ->updateOrCreate(
                [
                    'locality' => $locality,
                ],
                [
                    'attendance_sheet_id' => $sheet?->id,
                    'title' => $title,
                    'meeting_date' => $meetingDate,
                ]
            );
    }

    private function replaceLines(
        PrayerMeetingItem $item,
        array $lines
    ): void {
        $item->lines()->delete();

        foreach ($lines as $index => [$type, $marker, $content]) {
            PrayerMeetingItemLine::query()->create([
                'prayer_meeting_item_id' => $item->id,
                'line_type' => $type,
                'marker' => $marker,
                'content' => $content,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
