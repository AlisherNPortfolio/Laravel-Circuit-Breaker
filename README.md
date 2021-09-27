# Circuit Breaker Pattern

Pattern-ni ko'rishdan oldin, u hal qiladigan muammo bilan tanishib olaylik.

Ko'pgina dasturlarda tashqariga so'rov yuborish va u yerdan ma'lumot qabul qilish vazifasi uchrab turadi. Hammaga ma'lumki, tarmoq doim ham bir xilda ishlamaydi. Ba'zan, uzilishlar ham ro'y berib turadi. Tarmoqda uzilish tez-tez bo'lib tursa va shu paytda dasturingiz so'rov yuborsa, dasturingiz ishlashida noqulayliklar paydo bo'ladi.

Misol uchun, faraz qiling tashqi qidiruv xizmatidan foydalanadigan dastur qilyapsiz. Boya aytganimizday, tarmoqda uzilishlar sodir bo'lganda qidiruv xizmatida so'rovga javob berishda kechikishlar paydo bo'ladi yoki umuman javob kelmay qoladi.

Bu esa, albatta, sizning dasturingiz uchun yomon. Bunday holatlarda, dasturga qidiruv xizmatiga so'rov yuborishga imkon berish dasturni qisman yoki to'liq ishdan to'xtashiga olib kelishi mumkin.

Bunga yechim sifatida, circuit breaker pattern-ini olish mumkin.

> Circuit breaker pattern-i zamonaviy dasturlashda qo'llaniladigan design pattern-i hisoblanadi. U dasturdagi uzilish(xatolik)larni aniqlab, maintenance (texnik xizmat ko'rsatish) holatida tashqi tizimning vaqtincha ishlamay qolishi yoki kutilmagan kechikishlari doimiy takrorlanishini oldini olish logikasini enkapsulyatsiyalaydi.

Circuit breaker pattern-i servis javob bermagan holatda so'rovni ishga tushirishni oldini oladi.

Laravel-da circuit breaker uchun kerak bo'ladigan barcha narsalar bor:

* HTTP client
* RateLimitier

Circuit breaker pattern-idan oldin dasturimiz quyidagi ko'rinishda bo'lgan:

`ArticleController.php`:

```bash
class ArticleController extends Controller
{
    public function index()
    {
        $response = Http::get('https://fake-search-service.com/api/v1/search?q=Laravel');
        return view('test.index', ['articles' => $response->body()]);
    }
}
```

Bu yerda HTTP client exception tashlashdan oldin bir necha soniya kutadi.

Laravel-ning client-i so'rov timeout-ini aniqlash uchun kerakli metodga ega:

```bash
class ArticleController extends Controller
{
    public function index()
    {
        $response = Http::timeout(2)->get('https://fake-search-service.com/api/v1/search?q=Laravel');
        return view('test.index', ['articles' => $response->body()]);
    }
}
```

Ammo, biz hali ham API-dan javob kelmaganda so'rovni to'xtatmayapti.

```bash
class ArticleController extends Controller
{
    public function index()
    {
        $limiter = app(RateLimiter::class);
        $actionKey = 'search_service';
        $threshold = 10;

        try {
            if ($limiter->tooManyAttempts($actionKey, $threshold)) {
                return $this->failOrFallback();
            }
            $response = Http::timeout(2)->get('https://fake-search-service.com/api/v1/search?q=Laravel');
            return view('test.index', ['articles' => $response->body()]);
        } catch (Exception $e) {
            $limiter->hit($actionKey, Carbon::now()->addMinutes(15));

            return $this->failOrFallback();
        }
    }
}
```

Laravel-ning RateLimiter-i bilan qidiruv API-siga bo'lgan muvaffaqiyatsiz urinishlar sonini hisoblaymiz: `$limiter->hit($actionKey, Carbon::now()->addMinutes(15));`.

Urinishlar sonini hisoblab, biz dasturimizni berilgan vaqt ichida berilgan sondagi so'rovlardan ortiq so'rov yuborishining oldini olamiz.

Har bir so'rov yuborishdan oldin, muvaffaqiyatsiz urinishlar soni ko'payib ketganiga tekshiramiz `$limiter->tooManyAttempts($actionKey, $threshold)` va agar muvaffaqiyatsiz urinishlar berilgan chegara(threshold)dan o'tsa, API-ga so'rov yuborishni 15 daqiqaga to'xtatib qo'yamiz.

`failOrFallback` metodi bilan tashqi xizmat o'rniga boshqa biror ish, masalan, database-dan qidirishni amalaga oshirish yoki holatga mos xatolikni qaytarish mumkin.
