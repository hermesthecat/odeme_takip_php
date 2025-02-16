<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h2 class="mb-4">Sayfa Bulunamadı</h2>
        <p class="lead mb-5">
            Aradığınız sayfa mevcut değil veya taşınmış olabilir.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="javascript:history.back()" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i>
                Geri Dön
            </a>
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>
                Ana Sayfa
            </a>
        </div>

        <!-- SVG Illustration -->
        <div class="mt-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="320" height="200" viewBox="0 0 800 600" fill="none">
                <style>
                    @keyframes float {
                        0% { transform: translateY(0px); }
                        50% { transform: translateY(-20px); }
                        100% { transform: translateY(0px); }
                    }
                    #astronaut {
                        animation: float 6s ease-in-out infinite;
                    }
                </style>
                <rect width="800" height="600" fill="#f8f9fa"/>
                <!-- Stars -->
                <circle cx="400" cy="300" r="2" fill="#6c757d" opacity="0.5"/>
                <circle cx="600" cy="200" r="2" fill="#6c757d" opacity="0.5"/>
                <circle cx="200" cy="400" r="2" fill="#6c757d" opacity="0.5"/>
                <circle cx="650" cy="350" r="2" fill="#6c757d" opacity="0.5"/>
                <circle cx="150" cy="250" r="2" fill="#6c757d" opacity="0.5"/>
                <!-- Astronaut -->
                <g id="astronaut">
                    <path d="M400 300c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40z" fill="#0d6efd"/>
                    <path d="M380 240h40v40h-40z" fill="#fff"/>
                    <path d="M420 280h-40c0-11 9-20 20-20s20 9 20 20z" fill="#0d6efd"/>
                    <rect x="390" y="250" width="20" height="20" fill="#0d6efd"/>
                </g>
                <!-- Planet -->
                <circle cx="400" cy="450" r="100" fill="#e9ecef"/>
                <path d="M350 450c0-27.6 22.4-50 50-50s50 22.4 50 50-22.4 50-50 50-50-22.4-50-50z" fill="#dee2e6"/>
            </svg>
        </div>
    </div>
</div>

<script>
// Update dark mode colors for the SVG when dark mode is enabled
if(window.matchMedia('(prefers-color-scheme: dark)').matches) {
    const svg = document.querySelector('svg rect');
    if(svg) {
        svg.setAttribute('fill', '#212529');
    }
}
</script>
