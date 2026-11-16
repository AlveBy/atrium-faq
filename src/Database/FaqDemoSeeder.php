<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Faq\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;

/**
 * Questions buyers of the demo shop ask, on the default site, in English,
 * German and Russian. The German and Russian texts show once those
 * languages are on (the News demo adds them).
 */
final class FaqDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (Category::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $site = app(Sites::class)->default();

        $faq = [
            [[
                'en' => ['Delivery', 'delivery'],
                'de' => ['Lieferung', 'lieferung'],
                'ru' => ['Доставка', 'dostavka'],
            ], [
                [
                    'en' => ['How long does delivery take?', '<p>Two to three working days in the city, up to a week elsewhere in the country.</p>'],
                    'de' => ['Wie lange dauert die Lieferung?', '<p>Zwei bis drei Werktage in der Stadt, bis zu einer Woche im übrigen Land.</p>'],
                    'ru' => ['Сколько идёт доставка?', '<p>По городу — два-три рабочих дня, по стране — до недели.</p>'],
                ],
                [
                    'en' => ['Can I pick up my order in a store?', '<p>Yes. Choose the store at checkout; we will text you when the order is ready.</p>'],
                    'de' => ['Kann ich die Bestellung in einer Filiale abholen?', '<p>Ja. Wählen Sie die Filiale bei der Bestellung; wir schicken eine SMS, sobald sie bereitliegt.</p>'],
                    'ru' => ['Можно забрать заказ в магазине?', '<p>Да. Выберите магазин при оформлении — мы пришлём СМС, когда заказ будет готов.</p>'],
                ],
            ]],
            [[
                'en' => ['Payment', 'payment'],
                'de' => ['Bezahlung', 'bezahlung'],
                'ru' => ['Оплата', 'oplata'],
            ], [
                [
                    'en' => ['How can I pay?', '<p>By card online, or by card or cash when you pick up the order.</p>'],
                    'de' => ['Wie kann ich bezahlen?', '<p>Online mit Karte oder bei der Abholung mit Karte oder bar.</p>'],
                    'ru' => ['Как можно оплатить?', '<p>Картой на сайте или картой и наличными при получении.</p>'],
                ],
            ]],
            [[
                'en' => ['Warranty and returns', 'warranty'],
                'de' => ['Garantie und Rückgabe', 'garantie'],
                'ru' => ['Гарантия и возврат', 'garantiya'],
            ], [
                [
                    'en' => ['How long is the warranty?', '<p>Two years on every device, one year on batteries.</p>'],
                    'de' => ['Wie lange gilt die Garantie?', '<p>Zwei Jahre auf jedes Gerät, ein Jahr auf Akkus.</p>'],
                    'ru' => ['Какой срок гарантии?', '<p>Два года на любое устройство, год — на аккумуляторы.</p>'],
                ],
                [
                    'en' => ['Can I return a device I did not like?', '<p>Within 14 days, in its box and with everything that came in it.</p>'],
                    'de' => ['Kann ich ein Gerät zurückgeben, das mir nicht gefällt?', '<p>Innerhalb von 14 Tagen, in der Originalverpackung und vollständig.</p>'],
                    'ru' => ['Можно вернуть устройство, если оно не подошло?', '<p>В течение 14 дней, в коробке и в полной комплектации.</p>'],
                ],
            ]],
        ];

        $sort = 0;

        foreach ($faq as [$names, $questions]) {
            $category = new Category(['site_id' => $site->id, 'sort' => $sort += 100]);

            foreach ($names as $language => [$name, $code]) {
                $category->setTranslation('name', $language, $name)->setTranslation('code', $language, $code);
            }

            $category->save();

            foreach ($questions as $index => $texts) {
                $question = new Question(['site_id' => $site->id, 'category_id' => $category->id, 'sort' => ($index + 1) * 100]);

                foreach ($texts as $language => [$text, $answer]) {
                    $question->setTranslation('question', $language, $text)->setTranslation('answer', $language, $answer);
                }

                $question->save();
            }
        }

        return true;
    }
}
