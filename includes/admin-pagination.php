<?php
/**
 * Reusable admin pagination control.
 * Expects: $page (int, current page), $totalPages (int).
 * Keeps any existing query string params except 'page'.
 */
$queryParams = $_GET;
?>
<?php if ($totalPages > 1): ?>
<nav aria-label="Page navigation" class="mt-3 px-3">
    <ul class="pagination justify-content-end mb-0">
        <?php $queryParams['page'] = $page - 1; ?>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($queryParams) ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php $queryParams['page'] = $p; ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link <?= $p === $page ? 'text-white' : '' ?>" href="?<?= http_build_query($queryParams) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <?php $queryParams['page'] = $page + 1; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query($queryParams) ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>
