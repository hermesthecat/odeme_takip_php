<?php
// If user is logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}
?>
<!-- Hero Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Bütçenizi Akıllıca Yönetin</h1>
                <p class="lead mb-4">
                    Gelir ve giderlerinizi takip edin, tasarruf hedeflerinize ulaşın, 
                    faturalarınızı zamanında ödeyin.
                </p>
                <div class="d-flex gap-3">
                    <a href="/register" class="btn btn-light btn-lg">
                        Hemen Başlayın
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg">
                        Detaylı Bilgi
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="/assets/images/hero.svg" alt="Hero Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div id="features" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Özellikler</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-4 text-primary mb-3">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h5 class="card-title">Gelir Takibi</h5>
                        <p class="card-text">
                            Maaş, kira gibi düzenli ve tek seferlik gelirlerinizi kolayca kaydedin.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-4 text-danger mb-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h5 class="card-title">Gider Kontrolü</h5>
                        <p class="card-text">
                            Harcamalarınızı kategorize edin ve bütçe limitlerini aşmayın.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-4 text-success mb-3">
                            <i class="bi bi-piggy-bank"></i>
                        </div>
                        <h5 class="card-title">Birikim Hedefleri</h5>
                        <p class="card-text">
                            Hayallerinize ulaşmak için birikim hedefleri belirleyin ve takip edin.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h5 class="card-title">Fatura Hatırlatıcı</h5>
                        <p class="card-text">
                            Ödemelerinizi asla unutmayın, zamanında hatırlatma alın.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Benefits Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Neden Bizi Seçmelisiniz?</h2>
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="mb-4">
                    <h4><i class="bi bi-shield-check text-primary me-2"></i> Güvenli</h4>
                    <p>Verileriniz güvenle saklanır ve şifrelenir.</p>
                </div>
                <div class="mb-4">
                    <h4><i class="bi bi-phone text-primary me-2"></i> Mobil Uyumlu</h4>
                    <p>Tüm cihazlardan kolayca erişin ve yönetin.</p>
                </div>
                <div class="mb-4">
                    <h4><i class="bi bi-graph-up text-primary me-2"></i> Detaylı Raporlar</h4>
                    <p>Finansal durumunuzu grafiklerle analiz edin.</p>
                </div>
                <div>
                    <h4><i class="bi bi-currency-exchange text-primary me-2"></i> Çoklu Para Birimi</h4>
                    <p>Farklı para birimlerini otomatik dönüştürün.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="/assets/images/benefits.svg" alt="Benefits" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Get Started Section -->
<div class="py-5">
    <div class="container">
        <div class="text-center">
            <h2 class="mb-4">Finansal Hedeflerinize Ulaşın</h2>
            <p class="lead mb-4">
                Ücretsiz hesap oluşturun ve hemen kullanmaya başlayın.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/register" class="btn btn-primary btn-lg">
                    Hesap Oluştur
                </a>
                <a href="/login" class="btn btn-outline-primary btn-lg">
                    Giriş Yap
                </a>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">Sıkça Sorulan Sorular</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Uygulama ücretsiz mi?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Evet, uygulamamız tamamen ücretsizdir. Temel özelliklerin tümünü ücretsiz kullanabilirsiniz.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Verilerim güvende mi?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Tüm verileriniz güvenli sunucularda şifreli olarak saklanır ve sadece siz erişebilirsiniz.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Hangi para birimlerini destekliyorsunuz?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                TRY, USD, EUR ve GBP para birimlerini destekliyoruz. Otomatik kur güncellemesi yapılır.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Mobil uygulama var mı?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Şu an için mobil uygulamamız bulunmuyor ancak web sitemiz mobil cihazlara tam uyumludur.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
