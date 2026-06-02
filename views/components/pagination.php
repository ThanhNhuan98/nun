<?php
/**
 * @var int $currentPage
 * @var int $totalPages
 * @var array $queryParams
 */
$queryParams = $queryParams ?? [];
if (($totalPages ?? 1) <= 1) return;
?>
<div class="pagination-container" style="margin-top: 15px; margin-bottom: 30px;">
    <?php if (($currentPage ?? 1) > 1): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>" class="pagination-link">Trước</a>
    <?php else: ?>
        <span class="pagination-link disabled">Trước</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>" class="pagination-link <?= $i == $currentPage ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>" class="pagination-link">Sau</a>
    <?php else: ?>
        <span class="pagination-link disabled">Sau</span>
    <?php endif; ?>
</div>