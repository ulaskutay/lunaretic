<?php

namespace App\Etic\CMS;

use App\Etic\CMS\Models\Page;
use App\Etic\Theme\ActiveTheme;
use Illuminate\Support\Collection;

class CmsPageLayout
{
    public const PAGE = 'page';

    public const STORY = 'story';

    public const LEGAL = 'legal';

    public const CONTACT = 'contact';

    public const FAQ = 'faq';

    public function __construct(private ActiveTheme $theme) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Page $page): array
    {
        $template = $this->template($page);
        $html = trim((string) $page->content);
        $split = $this->splitContent($html);
        $faq = $template === self::FAQ ? $this->faqItems($html) : [];

        return [
            'template' => $template,
            'kicker' => $this->kicker($template),
            'lead' => $split['lead'],
            'body' => $split['body'],
            'content' => $html,
            'image' => $this->imageUrl(),
            'brand' => $this->theme->logoText(),
            'updated_at' => $page->updated_at?->format('d.m.Y'),
            'faq' => $faq,
            'related' => $this->related($page)->all(),
            'highlights' => $this->highlights(),
            'contacts' => $this->contacts(),
            'cta' => [
                'label' => 'Koleksiyonu keşfet',
                'url' => '/koleksiyon',
            ],
        ];
    }

    public function template(Page $page): string
    {
        $stored = (string) $page->template;

        if (in_array($stored, [self::STORY, self::LEGAL, self::CONTACT, self::FAQ], true)) {
            return $stored;
        }

        return $this->inferFromSlug((string) $page->slug);
    }

    public function kicker(string $template): string
    {
        return match ($template) {
            self::STORY => 'Marka',
            self::LEGAL => 'Yardım',
            self::CONTACT => 'Destek',
            self::FAQ => 'Sıkça sorulanlar',
            default => 'Bilgi',
        };
    }

    /**
     * @param  array<string, mixed>  $presentation
     */
    public function schemaJson(Page $page, string $canonical, array $presentation): string
    {
        $template = $presentation['template'] ?? self::PAGE;
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => match ($template) {
                self::STORY => 'AboutPage',
                self::CONTACT => 'ContactPage',
                self::FAQ => 'FAQPage',
                default => 'WebPage',
            },
            'name' => $page->title,
            'url' => $canonical,
        ];

        if ($template === self::FAQ && $presentation['faq'] !== []) {
            $payload['mainEntity'] = collect($presentation['faq'])
                ->map(fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags((string) $item['answer']),
                    ],
                ])
                ->values()
                ->all();
        } elseif (filled($presentation['lead'] ?? null)) {
            $payload['description'] = $presentation['lead'];
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @return array{lead: string|null, body: string}
     */
    public function splitContent(string $html): array
    {
        if (preg_match('/^\s*(<p\b[^>]*>.*?<\/p>)/is', $html, $match) === 1) {
            $lead = trim(html_entity_decode(strip_tags($match[1])));
            $body = trim(substr($html, strlen($match[0])));

            return [
                'lead' => $lead !== '' ? $lead : null,
                'body' => $body,
            ];
        }

        return [
            'lead' => null,
            'body' => $html,
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqItems(string $html): array
    {
        if (preg_match_all('/<h([23])\b[^>]*>(.*?)<\/h\1>(.*?)(?=<h[23]\b|$)/is', $html, $matches, PREG_SET_ORDER) < 1) {
            return [];
        }

        $items = [];

        foreach ($matches as $match) {
            $question = trim(html_entity_decode(strip_tags($match[2])));
            $answer = trim($match[3]);

            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    /**
     * @return Collection<int, array{title: string, slug: string, url: string}>
     */
    public function related(Page $page): Collection
    {
        $slugs = ['hakkimizda', 'sss', 'iletisim', 'kargo', 'iade', 'gizlilik', 'kullanim-kosullari'];

        return Page::query()
            ->forStore()
            ->where('is_published', true)
            ->whereIn('slug', $slugs)
            ->get(['title', 'slug'])
            ->sortBy(fn (Page $item) => array_search($item->slug, $slugs, true))
            ->values()
            ->map(fn (Page $item): array => [
                'title' => $item->title,
                'slug' => $item->slug,
                'url' => '/sayfa/'.$item->slug,
                'current' => $item->slug === $page->slug,
            ]);
    }

    /**
     * @return list<array{title: string, description: string}>
     */
    public function highlights(): array
    {
        return [
            [
                'title' => (string) $this->theme->setting('benefit_returns_title', 'Kolay iade'),
                'description' => (string) $this->theme->setting('benefit_returns_description', 'Siparişinizi 30 gün içinde kolayca iade edin.'),
            ],
            [
                'title' => (string) $this->theme->setting('benefit_shipping_title', 'Hızlı gönderim'),
                'description' => (string) $this->theme->setting('benefit_shipping_description', 'Siparişiniz özenle hazırlanır ve hızla kargoya verilir.'),
            ],
            [
                'title' => (string) $this->theme->setting('benefit_support_title', 'Müşteri desteği'),
                'description' => (string) $this->theme->setting('benefit_support_description', 'Sorularınız için ekibimiz her zaman yanınızda.'),
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, href: string|null, hint: string}>
     */
    public function contacts(): array
    {
        $contacts = [];
        $whatsapp = $this->theme->setting('social_whatsapp');

        if (filled($whatsapp)) {
            $digits = preg_replace('/\D+/', '', (string) $whatsapp);
            $contacts[] = [
                'label' => 'WhatsApp',
                'value' => (string) $whatsapp,
                'href' => $digits ? 'https://wa.me/'.$digits : null,
                'hint' => 'Sipariş ve ürün soruları',
            ];
        }

        $instagram = $this->theme->setting('social_instagram');

        if (filled($instagram)) {
            $contacts[] = [
                'label' => 'Instagram',
                'value' => 'Mesaj gönderin',
                'href' => (string) $instagram,
                'hint' => 'Yeni koleksiyonlar ve stil',
            ];
        }

        $contacts[] = [
            'label' => 'Müşteri desteği',
            'value' => 'Hafta içi 09:00–18:00',
            'href' => '/sayfa/sss',
            'hint' => 'SSS ve sipariş yardımı',
        ];

        $contacts[] = [
            'label' => 'Kargo & iade',
            'value' => 'Teslimat ve değişim',
            'href' => '/sayfa/kargo',
            'hint' => 'Süreçleri adım adım inceleyin',
        ];

        return array_slice($contacts, 0, 4);
    }

    public function imageUrl(): ?string
    {
        return $this->theme->editorialImageUrl()
            ?: $this->theme->secondaryEditorialImageUrl()
            ?: $this->theme->heroImageUrl()
            ?: $this->theme->footerImageUrl();
    }

    public function inferFromSlug(string $slug): string
    {
        return match ($slug) {
            'hakkimizda', 'about', 'marka', 'hikayemiz' => self::STORY,
            'iletisim', 'contact' => self::CONTACT,
            'sss', 'faq' => self::FAQ,
            'gizlilik', 'iade', 'kargo', 'kullanim-kosullari', 'privacy', 'returns', 'shipping', 'terms' => self::LEGAL,
            default => self::PAGE,
        };
    }
}
