<section class="py-5">
    <div class="container text-center" style="max-width:520px;">
        <i class="bi bi-exclamation-octagon display-3 text-danger mb-3" aria-hidden="true"></i>
        <h1 class="h3 fw-bold">Something went wrong</h1>
        <p class="text-muted mt-2 mb-4">
            We could not process your request. Please try again, or return to the home page.
        </p>
        <a href="/" class="btn btn-primary">
            <i class="bi bi-house" aria-hidden="true"></i>
            Return to home
        </a>
    </div>

    <?php if (($debug ?? false) && ($message ?? null)): ?>
    <!-- Debug detail — only shown when APP_DEBUG=true -->
    <div class="container mt-4">
        <div class="alert alert-danger text-start small">
            <strong>Debug (development only):</strong><br>
            <?= $message ?>
        </div>
        <pre class="bg-dark text-white p-3 rounded small overflow-auto"><?= $trace ?? '' ?></pre>
    </div>
    <?php endif ?>
</section>
