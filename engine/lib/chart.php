<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        var data = google.visualization.arrayToDataTable([
            <?php global $statisticsVisitors; global $current_locale;
            echo "['" . $current_locale["date"] . "', '" . $current_locale["visitors"] . "', '" . $current_locale["unique_visitors"] . "', '" . $current_locale["search_bots"] . "', '" . $current_locale["known_visitors"] . "'";
            if (count($statisticsVisitors->dayRecord) > 0) {
                foreach ($statisticsVisitors->dayRecord as $currentDay) {
                    echo "],['";
                    echo date("d.m.Y", $currentDay->time);
                    echo "',";
                    echo $currentDay->counter;
                    echo ",";
                    $unique = $currentDay->counter - $currentDay->known_counter;
                    echo $unique >= 0 ? $unique : 0;
                    echo ",";
                    echo $currentDay->bots_counter;
                    echo ",";
                    echo $currentDay->known_counter;
                }
            } else {
                echo "], ['" . $current_locale["no_data"] . "', 0, 0, 0, 0";
            }
            echo "]]);var options = {
            title: '" . $current_locale["attendance"] . "',
            hAxis: {title: '" . $current_locale["date"] . "', titleTextStyle: {color: '#333'}},"
            ?>
            vAxis
    :
        {
            minValue: 0
        }
    };

    var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
    chart.draw(data, options);
    }
</script>
