<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Modal;
use yii\bootstrap5\Button;

?>

<!-- Модальное окно для вывода сообщений об ошибках при связывании элементов -->
<?php Modal::begin([
    'id' => 'viewMessageErrorLinkingItemsModalForm',
    'title' => '<h3>' . Yii::t('app', 'ERROR_LINKING_ITEMS') . '</h3>',
]); ?>

    <div class="modal-body">
        <p id="message-text" style="font-size: 14px">
        </p>
    </div>

<?php $form = ActiveForm::begin([
    'id' => 'view-message-modal-form',
]); ?>

<?= Button::widget([
    'label' => Yii::t('app', 'BUTTON_OK'),
    'options' => [
        'class' => 'btn-success',
        'style' => 'margin:5px',
        'data-dismiss'=>'modal'
    ]
]); ?>

<?php ActiveForm::end(); ?>

<?php Modal::end(); ?>