import $ from 'jquery';
window.$ = window.jQuery = $;

// Event delegation safe to register immediately — no Semantic UI dependency
$(document).on('click', '.confirmable', function () {
    return confirm($(this).data('confirmable-label'));
});

// Wait for DOMContentLoaded: by then all defer scripts (semantic-ui, simplemde) have run
document.addEventListener('DOMContentLoaded', function () {
    $('.ui.dropdown').dropdown({ action: 'select' });
    $('.ui.checkbox').checkbox();
    $('.ui.accordion').accordion();

    const simpleMdeList = document.getElementsByClassName('simplemde');
    for (let i = 0; i < simpleMdeList.length; i++) {
        new SimpleMDE({
            element: simpleMdeList[i],
            spellChecker: false,
            hideIcons: ['side-by-side', 'fullscreen'],
        });
    }
});

// Highcharts — lazy import, data injected by stats.html.twig into window.*
if (window.location.href.includes('/admin/event/stats')) {
    import('highcharts').then(({ default: Highcharts }) => {
        if (window.chartConf) new Highcharts.Chart(window.chartConf);
        if (window.pieChartConf) new Highcharts.Chart('pieChartContainer', window.pieChartConf);
    });
}
