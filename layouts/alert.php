<?php

$flash = getFlash();

if ($flash):
?>

<div class="alerts-wrapper">

    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">

        <span class="alert-icon">
            <?php
            switch ($flash['type']) {
                case 'success':
                    echo '✅';
                    break;

                case 'error':
                    echo '❌';
                    break;

                case 'warning':
                    echo '⚠️';
                    break;

                default:
                    echo 'ℹ️';
                    break;
            }
            ?>
        </span>

        <span class="alert-msg">
            <?= e($flash['message']) ?>
        </span>

        <button class="alert-close" type="button">
            &times;
        </button>

    </div>

</div>

<?php endif; ?>