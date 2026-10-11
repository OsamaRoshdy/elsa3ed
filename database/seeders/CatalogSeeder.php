<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Fitment;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Creates the brands, models, categories and tags, then links each product to them by reading its name
 * (e.g. "تيل خلفي كورولا 2015" → category Brakes, Toyota Corolla 2015).
 *
 * Safe to run again: it only adds what is missing and never removes links made by the admin.
 *
 *   php artisan db:seed --class=CatalogSeeder
 */
class CatalogSeeder extends Seeder
{
    /**
     * Brand => model => spellings found in product names (brands match CarBrandsSeeder).
     *
     * @var array<string, array<string, list<string>>>
     */
    public const MODELS = [
        'Hyundai' => [
            'Elantra' => ['النترا', 'الانترا', 'ELANTRA'],
            'Accent' => ['اكسنت', 'نيواكسنت', 'ACCENT'],
            'Verna' => ['فيرنا', 'VERNA'],
            'Tucson' => ['توسان', 'TUCSON'],
            'Creta' => ['كريتا', 'CRETA'],
            'Sonata' => ['سوناتا', 'SONATA'],
            'Grand i10' => ['جراند I10', 'جراند I 10', 'GRAND I10'],
            'i10' => ['I10', 'I 10'],
            'i30' => ['I30', 'I 30'],
            'Matrix' => ['ماتركس', 'MATRIX'],
            'Getz' => ['جيتس', 'GETZ'],
            'Solaris' => ['سولاريس', 'SOLARIS'],
            'Terracan' => ['تريكان', 'تاريكان', 'TERRACAN'],
            'Equus' => ['اكوس', 'EQUUS'],
            'i20' => ['I20'],
            'ix35' => ['IX35', 'IX 35'],
            'Santa Fe' => ['سنتافي', 'سانتافي', 'سنتافى'],
            'H-1' => ['H-1', 'H1'],
            'Veloster' => ['فولستر', 'فيلوستر'],
        ],
        'Kia' => [
            'Cerato' => ['سيراتو', 'CERATO'],
            'Sportage' => ['سبورتاج', 'SPORTAGE'],
            'Sorento' => ['سورنتو', 'سورينتو', 'SORENTO'],
            'Rio' => ['ريو', 'RIO'],
            'Carens' => ['كارنز', 'CARENS'],
            'Soul' => ['سول', 'SOUL'],
            'Picanto' => ['بيكانتو', 'PICANTO'],
            'Seltos' => ['سيلتوس', 'سليتوس', 'SELTOS'],
            'Carnival' => ['كارنفال', 'CARNIVAL'],
            'XCeed' => ['XCEED'],
        ],
        'SsangYong' => [
            'Tivoli' => ['تيفولي', 'TIVOLI'],
        ],
        'Daewoo' => [
            'Nubira' => ['نوبيرا', 'NUBIRA'],
            'Lanos' => ['لانوس', 'LANOS'],
        ],
        'Toyota' => [
            'Corolla' => ['كورولا', 'كرولا', 'COROLLA'],
            'Camry' => ['كامري', 'كامرى', 'CAMRY'],
            'Yaris' => ['ياريس', 'YARIS'],
            'Auris' => ['اوريس', 'AURIS'],
            'C-HR' => ['C-HR', 'CHR'],
            'RAV4' => ['راف فور', 'راف 4', 'RAV4', 'RAV 4'],
            'Fortuner' => ['فورتشنر', 'فورتونر', 'FORTUNER'],
            'Hilux' => ['هايلكس', 'هيلوكس', 'HILUX'],
            'Land Cruiser' => ['لاند كروزر', 'لاندكروزر', 'LAND CRUISER'],
            'Prado' => ['برادو', 'PRADO'],
            'Hiace' => ['هايس', 'HIACE'],
            'Rush' => ['راش', 'RUSH'],
            'Avanza' => ['افانزا', 'AVANZA'],
            'Innova' => ['انوفا', 'INNOVA'],
            'Belta' => ['بيلتا', 'BELTA'],
        ],
        'Nissan' => [
            'Sunny' => ['صني', 'سني', 'SUNNY'],
            'Sentra' => ['سنترا', 'SENTRA'],
            'Tiida' => ['تيدا', 'تييدا', 'TIIDA'],
            'Micra' => ['ميكرا', 'MICRA'],
            'Altima' => ['التيما', 'ALTIMA'],
            'Juke' => ['جوك', 'JUKE'],
            'Kicks' => ['كيكس', 'KICKS'],
            'Qashqai' => ['قشقاي', 'كاشكاي', 'QASHQAI'],
            'X-Trail' => ['اكس تريل', 'اكستريل', 'X-TRAIL', 'XTRAIL'],
            'Patrol' => ['باترول', 'PATROL'],
            'Pathfinder' => ['باثفايندر', 'PATHFINDER'],
            'Navara' => ['نافارا', 'NAVARA'],
        ],
        'Honda' => [
            'Civic' => ['سيفيك', 'CIVIC'],
            'Accord' => ['اكورد', 'ACCORD'],
            'City' => ['هوندا سيتي', 'HONDA CITY'],
            'Jazz' => ['جاز', 'JAZZ'],
            'CR-V' => ['CR-V', 'CRV'],
            'HR-V' => ['HR-V', 'HRV'],
            'Pilot' => ['بايلوت', 'PILOT'],
        ],
        'Mazda' => [
            'Mazda 2' => ['مازدا 2', 'MAZDA 2', 'MAZDA2'],
            'Mazda 3' => ['مازدا 3', 'MAZDA 3', 'MAZDA3'],
            'Mazda 6' => ['مازدا 6', 'MAZDA 6', 'MAZDA6'],
            'CX-3' => ['CX-3', 'CX3'],
            'CX-5' => ['CX-5', 'CX5'],
            'CX-9' => ['CX-9', 'CX9'],
        ],
        'Mitsubishi' => [
            'Lancer' => ['لانسر', 'LANCER'],
            'Pajero' => ['باجيرو', 'PAJERO'],
            'Outlander' => ['اوتلاندر', 'OUTLANDER'],
            'ASX' => ['ASX'],
            'Attrage' => ['اتراج', 'ATTRAGE'],
            'Mirage' => ['ميراج', 'MIRAGE'],
            'Xpander' => ['اكسباندر', 'XPANDER'],
            'Eclipse Cross' => ['اكليبس كروس', 'ECLIPSE CROSS'],
            'L200' => ['L200'],
        ],
        'Subaru' => [
            'Impreza' => ['امبريزا', 'IMPREZA'],
            'Forester' => ['فورستر', 'FORESTER'],
            'Outback' => ['اوت باك', 'اوتباك', 'OUTBACK'],
            'XV' => ['سوبارو XV', 'SUBARU XV'],
            'Legacy' => ['ليجاسي', 'LEGACY'],
        ],
        'Suzuki' => [
            'Swift' => ['سويفت', 'SWIFT'],
            'Alto' => ['التو', 'ALTO'],
            'Celerio' => ['سيليريو', 'CELERIO'],
            'Ciaz' => ['سياز', 'CIAZ'],
            'Dzire' => ['ديزاير', 'DZIRE'],
            'Ertiga' => ['ارتيجا', 'ERTIGA'],
            'Baleno' => ['بالينو', 'BALENO'],
            'Vitara' => ['فيتارا', 'VITARA'],
            'Jimny' => ['جيمني', 'JIMNY'],
        ],
        'Lexus' => [
            'ES' => ['لكزس ES', 'LEXUS ES'],
            'IS' => ['لكزس IS', 'LEXUS IS'],
            'LS' => ['لكزس LS', 'LEXUS LS'],
            'NX' => ['لكزس NX', 'LEXUS NX'],
            'RX' => ['لكزس RX', 'LEXUS RX'],
            'GX' => ['لكزس GX', 'LEXUS GX'],
            'LX' => ['لكزس LX', 'LEXUS LX'],
        ],
        'Infiniti' => [
            'Q50' => ['Q50'],
            'QX50' => ['QX50'],
            'QX60' => ['QX60'],
            'QX80' => ['QX80', 'QX56'],
            'FX' => ['FX35', 'FX37', 'FX50'],
        ],
        'Acura' => [
            'MDX' => ['MDX'],
            'RDX' => ['RDX'],
            'TLX' => ['TLX'],
        ],
        'Daihatsu' => [
            'Terios' => ['تيريوس', 'TERIOS'],
            'Sirion' => ['سيريون', 'SIRION'],
            'Gran Max' => ['جران ماكس', 'GRAN MAX', 'GRANMAX'],
        ],
        'Isuzu' => [
            'D-Max' => ['دي ماكس', 'ديماكس', 'D-MAX', 'DMAX'],
            'MU-X' => ['MU-X', 'MUX'],
        ],
        'Datsun' => [
            'Go' => ['داتسون جو', 'DATSUN GO'],
        ],
    ];

    /**
     * Generation codes used in product names => [brand, model, first year, last year (null = still made)].
     *
     * @var array<string, array{0: string, 1: string, 2: int, 3: int|null}>
     */
    public const GENERATION_CODES = [
        'HD' => ['Hyundai', 'Elantra', 2007, 2010],
        'MD' => ['Hyundai', 'Elantra', 2011, 2015],
        'AD' => ['Hyundai', 'Elantra', 2016, 2020],
        'CN7' => ['Hyundai', 'Elantra', 2021, null],
        'RB' => ['Hyundai', 'Accent', 2011, 2018],
        'HCI' => ['Hyundai', 'Accent', 2018, null],
        'NX4' => ['Hyundai', 'Tucson', 2021, null],
        'NQ5' => ['Kia', 'Sportage', 2022, null],
        'K3' => ['Kia', 'Cerato', 2013, 2018],
    ];

    /**
     * Brand names written directly in product names.
     *
     * @var array<string, list<string>>
     */
    public const BRAND_WORDS = [
        'Hyundai' => ['هيونداي', 'هيونداى', 'HYUNDAI'],
        'Kia' => ['كيا', 'KIA'],
        'SsangYong' => ['سانج يونج', 'SSANGYONG'],
        'Daewoo' => ['دايو', 'دايوو', 'DAEWOO'],
        'Toyota' => ['تويوتا', 'TOYOTA'],
        'Nissan' => ['نيسان', 'NISSAN'],
        'Honda' => ['هوندا', 'HONDA'],
        'Mazda' => ['مازدا', 'MAZDA'],
        'Mitsubishi' => ['ميتسوبيشي', 'متسوبيشي', 'MITSUBISHI'],
        'Subaru' => ['سوبارو', 'SUBARU'],
        'Suzuki' => ['سوزوكي', 'SUZUKI'],
        'Lexus' => ['لكزس', 'LEXUS'],
        'Infiniti' => ['انفينيتي', 'INFINITI'],
        'Acura' => ['اكيورا', 'ACURA'],
        'Daihatsu' => ['دايهاتسو', 'DAIHATSU'],
        'Isuzu' => ['ايسوزو', 'ISUZU'],
        'Datsun' => ['داتسون', 'DATSUN'],
    ];

    /**
     * Category => part words. A product gets the category whose word appears first in its name.
     *
     * @var array<string, list<string>>
     */
    public const CATEGORIES = [
        'المرايات' => ['مرايا', 'مرايه', 'مرايات'],
        'الأبواب والأقفال' => ['باب', 'ابواب', 'اوكره', 'كالون', 'باكته', 'تجويف', 'مكانزم', 'مكانزيم', 'مقبض'],
        'التكييف والتبريد' => ['تكييف', 'تكيف', 'كباس', 'كمبروسر', 'سربنتينه', 'سربنتيه', 'سربتنينه', 'اكسبنشن', 'كوعه', 'ترموسات', 'ريداتير', 'رادياتير', 'مروحه', 'ترموستات', 'تبريد', 'طلمبه مياه', 'شجره مياه', 'مبرد'],
        'الموتور' => ['ماتور', 'موتور', 'محرك', 'مكنه', 'جوان', 'سلندر', 'بستم', 'شنابر', 'كاتينه', 'جنزير', 'اويل سيل', 'طبه زيت', 'كرنك', 'بلف', 'سير', 'شداد', 'هوايه', 'شكمان', 'كاتم صوت', 'طلمبه بنزين', 'بنزين', 'عوامه', 'اجزاء موتور', 'قاعده', 'طلمبه زيت', 'داكت', 'بوابه', 'صباع', 'مانفولد', 'مانفول', 'تربو', 'جوانات'],
        'الفرامل' => ['تيل', 'فرامل', 'طنبوره عجل', 'ديسك', 'دسك'],
        'الإضاءة' => ['فانوس', 'كشاف', 'شبوره', 'اسطب', 'لمبه', 'ليد', 'عاكس', 'غماز'],
        'العفشة والدركسيون' => ['مساعد', 'مقص', 'كوبايه', 'باره', 'دركسيون', 'دريكسيون', 'تيش', 'ميزان', 'كارتيره', 'جلبه', 'بليه', 'دلايه', 'صره', 'طاره', 'طنبوره', 'باور', 'بلي', 'دراع', 'شمعدان', 'دنجل', 'قنطره', 'عفشه', 'جنط'],
        'الفلاتر' => ['فلتر'],
        'الصدامات والشبك' => ['اكصدام', 'صدام', 'شبكه', 'نيكل', 'مصفحه', 'بطاحه'],
        'الكهرباء والحساسات' => ['حساس', 'بوجيه', 'بوجيهات', 'كويل', 'موبينه', 'دينامو', 'مارش', 'زرار', 'زراير', 'ايرباك', 'ضفيره', 'بطاريه', 'كلاكس', 'بوق', 'مفتاح', 'عمه', 'ساعه', 'اكتويتر', 'سويتش', 'كاميرا'],
        'الهيكل والصاج' => ['كبوت', 'رفرف', 'شنطه', 'صاج', 'كساحه', 'توربيدو', 'طابلوه', 'طبلوه', 'مفصله', 'سقف', 'مزراب', 'تانك', 'شاسيه', 'داير', 'قشره', 'حزام امان', 'صدر', 'اسطفه', 'علامه', 'كافر', 'رمش', 'كلبس'],
        'المساحات والزجاج' => ['مساحه', 'مساحات', 'زجاج', 'برابريز', 'ماتور قربه', 'رشاش'],
        'ناقل الحركة' => ['فتيس', 'كوبلن', 'دبرياج', 'عكس', 'شفت', 'شيفت', 'ترس'],
    ];

    /**
     * Generic words, only used when none of the part words above match (e.g. "خرطوم فرامل" stays Brakes).
     *
     * @var array<string, list<string>>
     */
    public const GENERIC_CATEGORY_WORDS = [
        'التكييف والتبريد' => ['خرطوم', 'مشترك'],
        'الهيكل والصاج' => ['غطاء', 'طبه', 'حامل', 'فبره', 'جلده', 'مسمار', 'صاموله'],
        'الموتور' => ['حزام'],
    ];

    /**
     * Category for products whose name has no known part word.
     */
    public const OTHER_CATEGORY = 'أخرى';

    /**
     * Tag => [colour, words in the product name]. Daftra's "oem" category also means original.
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    public const TAGS = [
        'أصلي' => ['emerald', ['اصلي', 'اصلى', 'جنيون', 'موبيس', 'MOBIS', 'GENUINE']],
        'تجاري كوري' => ['amber', ['كوري', 'كوريا']],
        'تجاري صيني' => ['rose', ['صيني', 'صينى']],
        'تجاري تايواني' => ['sky', ['تايواني', 'تايوانى', 'تايوان']],
    ];

    /**
     * @var array<string, int>
     */
    private array $stats = ['products' => 0, 'categorized' => 0, 'with_vehicle' => 0, 'tagged' => 0];

    /**
     * Seed the catalogue taxonomy and link the products.
     */
    public function run(): void
    {
        // Brands and their logos come from CarBrandsSeeder.
        $this->call(CarBrandsSeeder::class);
        $brands = collect(array_keys(self::BRAND_WORDS))->mapWithKeys(fn (string $name) => [$name => Brand::firstOrCreate(['name' => $name])]);

        $carModels = [];
        foreach (self::MODELS as $brandName => $models) {
            foreach (array_keys($models) as $modelName) {
                $carModels[$brandName][$modelName] = CarModel::firstOrCreate(['brand_id' => $brands[$brandName]->id, 'name' => $modelName]);
            }
        }

        $categories = collect([...array_keys(self::CATEGORIES), self::OTHER_CATEGORY])->mapWithKeys(fn (string $name) => [$name => Category::firstOrCreate(['name' => $name])]);
        $tags = collect(self::TAGS)->mapWithKeys(fn (array $tag, string $name) => [$name => Tag::firstOrCreate(['name' => $name], ['color' => $tag[0]])]);

        Product::query()->with('fitments')->chunkById(500, function (Collection $products) use ($brands, $carModels, $categories, $tags) {
            foreach ($products as $product) {
                $this->link($product, $brands, $carModels, $categories, $tags);
            }
        });

        $this->command?->info(sprintf(
            'Linked %d products: %d with a category, %d with a vehicle, %d with a tag.',
            $this->stats['products'], $this->stats['categorized'], $this->stats['with_vehicle'], $this->stats['tagged'],
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Brand>  $brands
     * @param  array<string, array<string, CarModel>>  $carModels
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     * @param  \Illuminate\Support\Collection<string, Tag>  $tags
     */
    private function link(Product $product, $brands, array $carModels, $categories, $tags): void
    {
        $name = self::normalize((string) $product->name);
        $this->stats['products']++;

        $categoryName = self::categoryFor($name) ?? self::categoryFor(self::normalize((string) $product->category));
        $other = $categories[self::OTHER_CATEGORY];
        if ($categoryName) {
            $product->categories()->syncWithoutDetaching([$categories[$categoryName]->id]);
            // A product that now has a real category leaves "Other".
            $product->categories()->detach($other->id);
            $this->stats['categorized']++;
        } elseif (! $product->categories()->exists()) {
            $product->categories()->attach($other->id);
        }

        $vehicles = self::vehiclesIn($name);
        $brandIds = collect(self::brandsIn($name))->merge(collect($vehicles)->pluck('brand'))->unique()->map(fn ($brandName) => $brands[$brandName]->id);
        if ($brandIds->isNotEmpty()) {
            $product->brands()->syncWithoutDetaching($brandIds->all());
        }

        foreach ($vehicles as $vehicle) {
            $product->fitments()->firstOrCreate([
                'car_model_id' => $carModels[$vehicle['brand']][$vehicle['model']]->id,
                'year_from' => $vehicle['year_from'],
                'year_to' => $vehicle['year_to'],
            ]);
        }
        if ($vehicles) {
            $this->stats['with_vehicle']++;
        }

        $tagIds = collect(self::tagsFor($name, (string) $product->category))->map(fn ($tagName) => $tags[$tagName]->id);
        if ($tagIds->isNotEmpty()) {
            $product->tags()->syncWithoutDetaching($tagIds->all());
            $this->stats['tagged']++;
        }
    }

    /**
     * Unifies Arabic spelling variants (أ إ آ → ا, ى → ي, ة → ه, no diacritics/tatweel) and upper-cases Latin letters.
     */
    public static function normalize(string $text): string
    {
        $text = preg_replace('/\p{Mn}|\x{0640}/u', '', $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);

        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($text)));
    }

    /**
     * The category whose part word appears first in the (normalized) name, falling back to the generic words.
     */
    public static function categoryFor(string $name): ?string
    {
        return self::earliestMatch($name, self::CATEGORIES) ?? self::earliestMatch($name, self::GENERIC_CATEGORY_WORDS);
    }

    /**
     * The key whose word appears first in the text (the longer word wins a tie).
     *
     * @param  array<string, list<string>>  $wordsByKey
     */
    private static function earliestMatch(string $text, array $wordsByKey): ?string
    {
        $best = null;

        foreach ($wordsByKey as $key => $words) {
            foreach ($words as $word) {
                $position = self::position($text, self::normalize($word));

                if ($position !== null && ($best === null || $position < $best[0] || ($position === $best[0] && mb_strlen($word) > $best[1]))) {
                    $best = [$position, mb_strlen($word), $key];
                }
            }
        }

        return $best[2] ?? null;
    }

    /**
     * Vehicles mentioned in the (normalized) name, with their years.
     *
     * @return list<array{brand: string, model: string, year_from: int|null, year_to: int|null}>
     */
    public static function vehiclesIn(string $name): array
    {
        $found = [];

        // Model names, optionally followed by a year ("النترا 2015") or a range ("اكسنت 2012-2015", "2018-").
        foreach (self::MODELS as $brand => $models) {
            foreach ($models as $model => $spellings) {
                foreach ($spellings as $spelling) {
                    $spelling = self::normalize($spelling);
                    if (! preg_match_all('/(?<![\p{L}\p{N}])'.preg_quote($spelling, '/').'(?![\p{L}])/u', $name, $matches, PREG_OFFSET_CAPTURE)) {
                        continue;
                    }
                    foreach ($matches[0] as [$match, $offset]) {
                        // mb_strcut keeps whole characters (a cut Arabic letter would make the /u regex fail).
                        $after = mb_strcut($name, $offset + strlen($match), 40);
                        [$from, $to] = self::yearsAt($after);
                        $found[] = ['brand' => $brand, 'model' => $model, 'year_from' => $from, 'year_to' => $to];
                    }
                }
            }
        }

        // Generation codes ("MD", "CN7", ...) give a year range; they replace a single year of the same model inside that range.
        foreach (self::GENERATION_CODES as $code => [$brand, $model, $from, $to]) {
            if (! preg_match('/(?<![A-Z])'.$code.'(?![A-Z0-9])/u', $name)) {
                continue;
            }
            // An explicit range in the name ("النترا 2008-2018 (HD)") is more precise than the code.
            if (collect($found)->contains(fn ($vehicle) => $vehicle['model'] === $model && $vehicle['year_from'] !== $vehicle['year_to'])) {
                continue;
            }
            $found = array_values(array_filter($found, fn ($vehicle) => ! (
                $vehicle['model'] === $model
                && ($vehicle['year_from'] === null || ($vehicle['year_from'] === $vehicle['year_to'] && $vehicle['year_from'] >= $from && ($to === null || $vehicle['year_from'] <= $to)))
            )));
            $found[] = ['brand' => $brand, 'model' => $model, 'year_from' => $from, 'year_to' => $to];
        }

        // "Grand i10" also matches "i10": keep the more specific one.
        if (collect($found)->contains('model', 'Grand i10')) {
            $found = array_values(array_filter($found, fn ($vehicle) => $vehicle['model'] !== 'i10'));
        }

        // A model mentioned with years doesn't also need an "all years" entry.
        $withYears = collect($found)->filter(fn ($vehicle) => $vehicle['year_from'] || $vehicle['year_to'])->pluck('model')->all();
        $found = array_filter($found, fn ($vehicle) => $vehicle['year_from'] || $vehicle['year_to'] || ! in_array($vehicle['model'], $withYears, true));

        return array_values(collect($found)->unique(fn ($vehicle) => implode('|', $vehicle))->all());
    }

    /**
     * Brands written directly in the (normalized) name.
     *
     * @return list<string>
     */
    public static function brandsIn(string $name): array
    {
        return array_keys(array_filter(self::BRAND_WORDS, fn (array $words) => collect($words)->contains(fn ($word) => self::position($name, self::normalize($word)) !== null)));
    }

    /**
     * @return list<string>
     */
    public static function tagsFor(string $name, string $daftraCategory): array
    {
        $tags = array_keys(array_filter(self::TAGS, fn (array $tag) => collect($tag[1])->contains(fn ($word) => self::position($name, self::normalize($word)) !== null)));

        if (mb_strtolower(trim($daftraCategory)) === 'oem' && ! in_array('أصلي', $tags, true)) {
            $tags[] = 'أصلي';
        }

        return $tags;
    }

    /**
     * Years written right after a model name: [from, to]; a single year gives [year, year].
     *
     * @return array{0: int|null, 1: int|null}
     */
    private static function yearsAt(string $text): array
    {
        if (! preg_match('/^\s*\(?((?:19|20)\d{2})(?!\d)(?:\s*-\s*((?:19|20)\d{2})(?!\d)|(-)(?=\s*(?:$|[()\/])))?/u', $text, $match)) {
            return [null, null];
        }

        $from = (int) $match[1];
        $to = ! empty($match[2]) ? (int) $match[2] : (! empty($match[3]) ? null : $from);

        if ($from < Fitment::FIRST_YEAR || $from > Fitment::lastYear() || ($to !== null && ($to < $from || $to > Fitment::lastYear()))) {
            return [null, null];
        }

        return [$from, $to];
    }

    /**
     * Position of a whole word/phrase in the text, or null.
     */
    private static function position(string $text, string $word): ?int
    {
        // Arabic words may carry a prefix: "الشبوره", "بالحساس", "والفانوس".
        if (! preg_match('/(?<![\p{L}])(?:ال|بال|وال|و|ب)?'.preg_quote($word, '/').'(?![\p{L}])/u', $text, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        return mb_strlen(substr($text, 0, $match[0][1]));
    }
}
