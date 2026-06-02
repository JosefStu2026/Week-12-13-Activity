<?php
// We use the built-in setSurroundingNum directly on the renderer's internal pointer if supported,
// but the safest, framework-approved method is to let the Pager config handle it, or use the native links array loop!
?>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center shadow-sm">
        
        <?php if ($pager->hasPrevious()) : ?>
            <li class="page-item">
                <a class="page-link text-dark" href="<?= $pager->getFirst() ?>" aria-label="First">
                    <span aria-hidden="true">&laquo;&laquo; First</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link text-dark" href="<?= $pager->getPrevious() ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo; Prev</span>
                </a>
            </li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                <a class="page-link <?= $link['active'] ? 'bg-dark border-dark text-white' : 'text-dark' ?>" href="<?= $link['uri'] ?>">
                    <?= $link['title'] ?>
                </a>
            </li>
        <?php endforeach ?>

        <?php if ($pager->hasNext()) : ?>
            <li class="page-item">
                <a class="page-link text-dark" href="<?= $pager->getNext() ?>" aria-label="Next">
                    <span aria-hidden="true">Next &raquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link text-dark" href="<?= $pager->getLast() ?>" aria-label="Last">
                    <span aria-hidden="true">Last &raquo;&raquo;</span>
                </a>
            </li>
        <?php endif ?>
        
    </ul>
</nav>