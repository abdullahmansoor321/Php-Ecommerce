<?php
/**
 * Reusable admin filter toolbar: a search box plus any number of select filters.
 * Submits via GET back to the current page, so `page` is intentionally NOT
 * carried over - a new search always starts from page 1. Existing links keep
 * their params through admin-pagination.php, which reads $_GET directly.
 *
 * Expects (set by the calling page before including this file):
 *   $filterSearchTerm  string  current ?q= value
 *   $filterResultCount int     rows matched by the current filters
 *   $filterResultNoun  string  plural noun for the count, e.g. 'products'
 *   $filterSelects     array   optional, name => [
 *                                 'label'   => 'Status',
 *                                 'value'   => 'pending',   // current selection
 *                                 'options' => ['' => 'All', 'pending' => 'Pending'],
 *                             ]
 */
$filterSearchTerm  = isset($filterSearchTerm) ? $filterSearchTerm : '';
$filterResultCount = isset($filterResultCount) ? $filterResultCount : 0;
$filterResultNoun  = isset($filterResultNoun) ? $filterResultNoun : 'results';
$filterSelects     = isset($filterSelects) ? $filterSelects : [];

// A filter is "active" when the search box has text or any select is non-empty.
$filtersActive = $filterSearchTerm !== '';
foreach ($filterSelects as $filterSelect) {
    if ((string)$filterSelect['value'] !== '') {
        $filtersActive = true;
        break;
    }
}
?>
<form action="" method="get" class="px-4 pt-4 pb-2" role="search">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0"><i class="material-symbols-rounded">search</i></span>
                <input type="search" name="q" class="form-control border-start-0" value="<?= htmlspecialchars($filterSearchTerm) ?>" placeholder="Search&hellip;" aria-label="Search">
            </div>
        </div>
        <?php foreach ($filterSelects as $filterName => $filterSelect): ?>
            <div class="col-12 col-md-auto">
                <select name="<?= htmlspecialchars($filterName) ?>" class="form-select form-select-sm" aria-label="<?= htmlspecialchars($filterSelect['label']) ?>" onchange="this.form.submit()">
                    <?php foreach ($filterSelect['options'] as $filterValue => $filterLabel): ?>
                        <option value="<?= htmlspecialchars((string)$filterValue) ?>"<?= (string)$filterSelect['value'] === (string)$filterValue ? ' selected' : '' ?>><?= htmlspecialchars($filterLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <div class="col-12 col-md-auto">
            <button type="submit" class="btn btn-sm btn-dark mb-0"><i class="material-symbols-rounded text-sm me-1">search</i>Search</button>
        </div>
    </div>
</form>
<div class="d-flex justify-content-between align-items-center px-4 pb-2">
    <span class="text-xs text-secondary">
        <?= (int)$filterResultCount ?> <?= htmlspecialchars($filterResultNoun) ?><?= $filtersActive ? ' matching your filters' : '' ?>
    </span>
    <?php if ($filtersActive): ?>
        <a href="?" class="text-xs font-weight-bold text-dark">Clear filters</a>
    <?php endif; ?>
</div>