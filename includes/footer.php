    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Ödeme Takip</h4>
                <p>Kişisel finans yönetimi için basit ve kullanışlı bir araç.</p>
            </div>

            <div class="footer-section">
                <h4>Hızlı Erişim</h4>
                <ul>
                    <li><a href="/pages/income.php">Gelirler</a></li>
                    <li><a href="/pages/expenses.php">Giderler</a></li>
                    <li><a href="/pages/savings.php">Birikimler</a></li>
                    <li><a href="/pages/bills.php">Faturalar</a></li>
                    <li><a href="/pages/reports.php">Raporlar</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Yardımcı Bağlantılar</h4>
                <ul>
                    <li><a href="/help.php">Yardım</a></li>
                    <li><a href="/faq.php">SSS</a></li>
                    <li><a href="/privacy.php">Gizlilik Politikası</a></li>
                    <li><a href="/terms.php">Kullanım Koşulları</a></li>
                    <li><a href="/contact.php">İletişim</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Bağlantıda Kalın</h4>
                <div class="social-links">
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Ödeme Takip Sistemi - A. Kerem Gök</p>
            <p>Sürüm <?php echo defined('API_VERSION') ? API_VERSION : '1.0.0'; ?></p>
        </div>
    </footer>

    <!-- Çevrimdışı Bildirimi -->
    <div id="offlineAlert" class="offline-alert" style="display: none;">
        <i class="fas fa-wifi-slash"></i>
        Çevrimdışı mod - İnternet bağlantısı yok
    </div>

    <!-- Yükleniyor Göstergesi -->
    <div id="loadingIndicator" class="loading-indicator" style="display: none;">
        <div class="spinner"></div>
        <p>Yükleniyor...</p>
    </div>

    <!-- CSRF Token -->
    <script>
        const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
    </script>

    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(registration => {
                    console.log('Service Worker başarıyla kaydedildi:', registration);
                })
                .catch(error => {
                    console.log('Service Worker kaydı başarısız:', error);
                });
        }
    </script>
    </body>

    </html>