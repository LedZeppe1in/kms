<?php

/* @var $this yii\web\View */
/* @var $model app\modules\main\models\Diagram */
/* @var $transition_model app\modules\stde\models\Transition */
/* @var $states_all app\modules\stde\controllers\StateTransitionDiagramsController */

use yii\helpers\Html;
use app\modules\main\models\Lang;

$this->title = Yii::t('app', 'DIAGRAMS_PAGE_VISUAL_DIAGRAM') . ' - ' . $model->name;

$this->params['menu_add'] = [
];

$this->params['menu_diagram'] = [
];
?>



<?php

// создаем массив из state для передачи в js
$states_mas = array();
foreach ($states_model_all as $s){
    array_push($states_mas, [$s->id, $s->indent_x, $s->indent_y]);
}

// создаем массив из transition для передачи в jsplumb
$transitions_mas = array();
foreach ($transitions_model_all as $t){
    array_push($transitions_mas, [$t->id, $t->name, $t->description, $t->state_from, $t->state_to]);
}

?>


<?= $this->render('_modal_form_transition_editor', [
    'model' => $model,
    'transition_model' => $transition_model,
]) ?>


<!-- Подключение скрипта для модальных форм -->
<?php
$this->registerJsFile('/js/modal-form.js', ['position' => yii\web\View::POS_HEAD]);
$this->registerCssFile('/css/state-transition-diagram.css', ['position'=>yii\web\View::POS_HEAD]);
$this->registerJsFile('/js/jsplumb.js', ['position'=>yii\web\View::POS_HEAD]);  // jsPlumb 2.12.9
?>





<script type="text/javascript">
    var guest = <?php echo json_encode(Yii::$app->user->isGuest); ?>;//переменная гость определяет пользователь гость или нет

    var current_connection; //текущее соединение
    var id_state_from = 0; //id состояние из которого выходит связь
    var id_state_to = 0; //id состояния к которому выходит связь

    var states_mas = <?php echo json_encode($states_mas); ?>;//прием массива состояний из php
    var transitions_mas = <?php echo json_encode($transitions_mas); ?>;//прием массива переходов из php


    var mas_data_state = {};
    var q = 0;
    var id = "";
    var indent_x = "";
    var indent_y = "";
    $.each(states_mas, function (i, mas) {
        $.each(mas, function (j, elem) {
            if (j == 0) {id = elem;}//записываем id
            if (j == 1) {indent_x = elem;}
            if (j == 2) {indent_y = elem;}
            mas_data_state[q] = {
                "id":id,
                "indent_x":indent_x,
                "indent_y":indent_y,
            }
        });
        q = q+1;
    });

    //console.log(mas_data_state);


    var mas_data_transition = {};
    var q = 0;
    var id = "";
    var name = "";
    var description = "";
    var state_from = "";
    var state_to = "";
    $.each(transitions_mas, function (i, mas) {
        $.each(mas, function (j, elem) {
            if (j == 0) {id = elem;}//записываем id
            if (j == 1) {name = elem;}
            if (j == 2) {description = elem;}
            if (j == 3) {state_from = elem;}
            if (j == 4) {state_to = elem;}
            mas_data_transition[q] = {
                "id":id,
                "name":name,
                "description":description,
                "state_from":state_from,
                "state_to":state_to,
            }
        });
        q = q+1;
    });

    //console.log(mas_data_transition);


    //-----начало кода jsPlumb-----
    var instance = "";
    jsPlumb.ready(function () {
        instance = jsPlumb.getInstance({
            Connector:["StateMachine"], //стиль соединения линии
            Endpoint:["Dot", {radius:3}], //стиль точки соединения
            EndpointStyle: { fill: '#337ab7' }, //цвет точки соединения
            PaintStyle : { strokeWidth:3, stroke: "#337ab7", "dashstyle": "0 0", fill: "transparent"},//стиль линии
            HoverPaintStyle : { strokeWidth:3, stroke: "#5bb35b", "dashstyle": "0 0", fill: "transparent"},//стиль линии при наведении
            Overlays:[["PlainArrow", {location:1, width:15, length:15}]], //стрелка
            Container: "visual_diagram"
        });


        //var div_visual_diagram = document.getElementById('visual_diagram');
        ////создаем группу с определенным именем group
        //instance.addGroup({
        //    el: div_visual_diagram,
        //    id: 'group',
        //    draggable: false, //перетаскивание группы
        //    //constrain: true, //запрет на перетаскивание элементов за группу (false перетаскивать можно)
        //    dropOverride:true,
        //});


        //находим все элементы с классом div-state и делаем их двигаемыми
        $(".div-state").each(function(i) {
            var id_state = $(this).attr('id');
            var state = document.getElementById(id_state);
            //делаем state перетаскиваемыми
            instance.draggable(state);
            //добавляем элемент state в группу с именем group
            //instance.addToGroup('group', state);
        });


        //находим все элементы с классом div-transition и делаем их двигаемыми
        $(".div-transition").each(function(i) {
            var id_state = $(this).attr('id');
            var state = document.getElementById(id_state);
            //делаем state перетаскиваемыми
            instance.draggable(state);
            //добавляем элемент state в группу с именем group
            //instance.addToGroup('group', state);
        });


        var windows = jsPlumb.getSelector(".div-state");

        //построение переходов (связей)
        instance.batch(function () {

            for (var i = 0; i < windows.length; i++) {

                instance.makeSource(windows[i], {
                    filter: ".connect-state",
                    anchor: "Continuous", //непрерывный анкер
                });

                instance.makeTarget(windows[i], {
                    dropOptions: { hoverClass: "dragHover" },
                    anchor: "Continuous", //непрерывный анкер
                    allowLoopback: true, // Разрешение создавать кольцевую связь
                });
            }

            //---------------построение связей из бд
            $.each(mas_data_transition, function (j, elem) {
                var c = instance.connect({
                    source: "state_" + elem.state_from,
                    target: "state_" + elem.state_to,
                    overlays: [
                        ['Label', {
                            label: elem.name,
                            location: 0.5, //расположение посередине
                            cssClass: "transitions-style"
                        }]
                    ],
                });
                //создаем параметр для связи id_transition куда прописываем название связи "transition_connect_" +  data['id'] (как замена id)
                c.setParameter('id_transition',"transition_connect_" +  elem.id);
            });
        });


        //обработка клика на связь для просмотра перехода
        instance.bind("click", function (c) {
            //получение id_transition параметра для идентификации связи
            var id_transition = parseInt(c.getParameters().id_transition.match(/\d+/));

            //поиск перехода относящегося к выбранной связи
            var transition = document.getElementById("transition_" + id_transition);
            if (transition.style.visibility == 'hidden'){
                transition.style.visibility = 'visible';//делаем переход видимым
            } else {
                transition.style.visibility='hidden';//скрываем переход
            }

            //поиск связанных элементов
            var source = document.getElementById(c.sourceId);
            var target = document.getElementById(c.targetId);

            var x_source = source.offsetLeft;//нахождение отступа слева от первого элемента
            var x_target = target.offsetLeft;//нахождение отступа слева от второго элемента
            var distance_x = Math.abs(x_source - x_target); //расстояние между элементами
            //нахождение отступа от крайнего слева элемента
            if (x_source < x_target){
                var indent_x = x_source;
            } else {
                var indent_x = x_target;
            }

            var y_source = source.offsetTop;//нахождение отступа сверху от первого элемента
            var y_target = target.offsetTop;//нахождение отступа сверху от второго элемента
            var distance_y = Math.abs(y_source - y_target); //расстояние между элементами
            //нахождение отступа от крайнего слева элемента
            if (y_source < y_target){
                var indent_y = y_source;
            } else {
                var indent_y = y_target;
            }

            //выравниваем переход по центру между связанными элементами
            transition.style.left = distance_x/2 + indent_x + 'px';
            transition.style.top = distance_y/2 + indent_y + 'px';
        });


        //обработка построения связи (добавление перехода)
        instance.bind("connection", function(connection) {
            if (!guest) {
                var source_id = connection.sourceId;
                var target_id = connection.targetId;

                //параметры передаваемые на модальную форму
                current_connection = connection;
                id_state_from = parseInt(source_id.match(/\d+/));
                id_state_to = parseInt(target_id.match(/\d+/));

                $("#addTransitionModalForm").modal("show");
            }
        });

    });
    //-----конец кода jsPlumb-----

</script>




<div class="state-transition-diagram-visual-diagram">
    <h1><?= Html::encode($this->title) ?></h1>
</div>

<div id="visual_diagram" class="visual-diagram col-md-12">

    <div id="visual_diagram_field" class="visual-diagram-top-layer">

        <!-- отображение состояний -->
        <?php foreach ($states_model_all as $state): ?>
            <div id="state_<?= $state->id ?>" class="div-state" title="<?= $state->description ?>">
                <div class="content-state">
                    <div id="state_name_<?= $state->id ?>" class="div-state-name"><?= $state->name ?></div>
                    <div class="connect-state glyphicon-share-alt" title="<?php echo Yii::t('app', 'BUTTON_CONNECTION'); ?>"></div>
                    <div id="state_del_<?= $state->id ?>" class="del-state glyphicon-trash" title="<?php echo Yii::t('app', 'BUTTON_DELETE'); ?>"></div>
                    <div id="state_edit_<?= $state->id ?>" class="edit-state glyphicon-pencil" title="<?php echo Yii::t('app', 'BUTTON_EDIT'); ?>"></div>
                    <div id="state_add_property_<?= $state->id ?>" class="add-state-property glyphicon-plus" title="<?php echo Yii::t('app', 'BUTTON_ADD'); ?>"></div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- отображение блоков переходов -->
        <?php foreach ($transitions_model_all as $transition): ?>
            <div id="transition_<?= $transition->id ?>" class="div-transition" style="visibility:hidden;">
                <div class="content-transition">
                    <div id="transition_name_<?= $transition->id ?>" class="div-transition-name"><?= $transition->name ?></div>
                    <div id="transition_del_<?= $transition->id ?>" class="del-transition glyphicon-trash" title="<?php echo Yii::t('app', 'BUTTON_DELETE'); ?>"></div>
                    <div id="transition_edit_<?= $transition->id ?>" class="edit-transition glyphicon-pencil" title="<?php echo Yii::t('app', 'BUTTON_EDIT'); ?>"></div>
                    <div id="transition_hide_<?= $transition->id ?>" class="hide-transition glyphicon-eye-close" title="<?php echo Yii::t('app', 'BUTTON_HIDE'); ?>"></div>
                    <div id="transition_add_property_<?= $transition->id ?>" class="add-transition-property glyphicon-plus" title="<?php echo Yii::t('app', 'BUTTON_ADD'); ?>"></div>
                </div>

                <!-- отображение условий -->
                <?php foreach ($transitions_property_model_all as $transition_property): ?>
                    <?php if ($transition_property->transition == $transition->id){ ?>
                        <div id="transition_property_<?= $transition_property->id ?>" class="div-transition-property">
                            <?= $transition_property->name ?> <?= $transition_property->getOperatorName()?> <?= $transition_property->value ?>
                            <div class="button-transition-property">
                                <div id="transition_property_edit_<?= $transition_property->id ?>" class="edit-transition-property glyphicon-pencil" title="<?php echo Yii::t('app', 'BUTTON_EDIT'); ?>"></div>
                                <div id="transition_property_del_<?= $transition_property->id ?>" class="del-transition-property glyphicon-trash" title="<?php echo Yii::t('app', 'BUTTON_DELETE'); ?>"></div>
                            </div>
                        </div>
                    <?php } ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

    </div>

</div>