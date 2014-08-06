<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

require_once DP_BASE_DIR.'/modules/macroprojects/projectstats.php';
GLOBAL $m;

$stats = array();
$pStats = array();

$stats = getTasksStats($prj);
$pStats = getProjectsStats($prj);


echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jquery.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jquery.jqplot.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.barRenderer.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.categoryAxisRenderer.min.js\"></script>\n";
echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/jqplot.pointLabels.min.js\"></script>\n";
//echo "<script type=\"text/javascript\" src=\"{$base}modules/$m/js/excanvas.min.js\"></script>\n";
echo "<!--[if lt IE 9]><script language=\"javascript\" type=\"text/javascript\" src=\"{$base}modules/$m/js/excanvas.min.js\"></script><![endif]-->";
?>

<script class="code" type="text/javascript">
$(document).ready(function(){
		$.jqplot.config.enablePlugins = true;
        var s1 = [2, 6, 7, 10];
        var ticks = ['Completion Rate', 'Progress Rate', 'On Going', 'Delayed' ];
        var projects = <?php echo json_encode($pStats)?> ;
		var tasks = <?php echo json_encode($stats)?> ;
		var baseUrl = <?php echo "\"{$base}\"" ?> ;
		var s2 = new Array();
		var s3 = new Array();
		var ticks2 = new Array();
		var barColors = new Array();
		var x;
		var urlSet = new Array();
		s2 = [parseInt(projects.p_completed_toactive),parseInt(projects.completion_percentage),parseInt(projects.started),parseInt(projects.p_onschedule)];
		s3 = [(100-parseInt(projects.p_completed_toactive)),(100-parseInt(projects.completion_percentage)),parseInt(projects.not_started),(100-parseInt(projects.p_onschedule))];
		
		s4 = [parseInt(tasks.p_completed),parseInt(tasks.completion_percentage),parseInt(tasks.started),parseInt(tasks.p_onschedule)];
		s5 = [(100-parseInt(tasks.p_completed)),(100-parseInt(tasks.completion_percentage)),parseInt(tasks.not_started),(100-parseInt(tasks.p_onschedule))];
		
        plot1 = $.jqplot('chart1', [s2,s3], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
		
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal'
				},
				pointLabels: {show: true, formatString: '%d'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks
                },
				xaxis:{
					
					tickOptions:{formatString:'%d'}
				}
            },
			title: {
				text : 'Summary Projects',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
		$('#chart1 .jqplot-point-0.jqplot-series-0').text( $('#chart1 .jqplot-point-0.jqplot-series-0').text() + '\% completed');
		$('#chart1 .jqplot-point-0.jqplot-series-1').text( $('#chart1 .jqplot-point-0.jqplot-series-1').text() + '\% remaining');
		$('#chart1 .jqplot-point-1.jqplot-series-0').text( $('#chart1 .jqplot-point-1.jqplot-series-0').text() + '\% completed');
		$('#chart1 .jqplot-point-1.jqplot-series-1').text( $('#chart1 .jqplot-point-1.jqplot-series-1').text() + '\% remaining');
		$('#chart1 .jqplot-point-2.jqplot-series-0').text( $('#chart1 .jqplot-point-2.jqplot-series-0').text() + ' projects started');
		$('#chart1 .jqplot-point-2.jqplot-series-1').text( $('#chart1 .jqplot-point-2.jqplot-series-1').text() + ' projects not started');
		$('#chart1 .jqplot-point-3.jqplot-series-0').text( $('#chart1 .jqplot-point-3.jqplot-series-0').text() + '\% ontime');
		$('#chart1 .jqplot-point-3.jqplot-series-1').text( $('#chart1 .jqplot-point-3.jqplot-series-1').text() + '\% delayed');
		
		
		plot1 = $.jqplot('chart2', [s4,s5], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
		
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal'
				},
				pointLabels: {show: true, formatString: '%d'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks
                },
				xaxis:{
					
					tickOptions:{formatString:'%d'}
				}
            },
			title: {
				text : 'Summary Tasks',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
		
		$('#chart2 .jqplot-point-0.jqplot-series-0').text( $('#chart2 .jqplot-point-0.jqplot-series-0').text() + '\% completed');
		$('#chart2 .jqplot-point-0.jqplot-series-1').text( $('#chart2 .jqplot-point-0.jqplot-series-1').text() + '\% remaining');
		$('#chart2 .jqplot-point-1.jqplot-series-0').text( $('#chart2 .jqplot-point-1.jqplot-series-0').text() + '\% completed');
		$('#chart2 .jqplot-point-1.jqplot-series-1').text( $('#chart2 .jqplot-point-1.jqplot-series-1').text() + '\% remaining');
		$('#chart2 .jqplot-point-2.jqplot-series-0').text( $('#chart2 .jqplot-point-2.jqplot-series-0').text() + ' tasks started');
		$('#chart2 .jqplot-point-2.jqplot-series-1').text( $('#chart2 .jqplot-point-2.jqplot-series-1').text() + ' tasks not started');
		$('#chart2 .jqplot-point-3.jqplot-series-0').text( $('#chart2 .jqplot-point-3.jqplot-series-0').text() + '\% ontime');
		$('#chart2 .jqplot-point-3.jqplot-series-1').text( $('#chart2 .jqplot-point-3.jqplot-series-1').text() + '\% delayed');
     /*
        $('#chart1').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                location.href = urlSet[pointIndex];
            }
        );
     
		$(".jqplot-yaxis-tick").css({
			cursor: "pointer",
			zIndex: "1"
		}).click(function(){ });
		*/
    });
</script>
<div style="position: absolute; margin-left: 40%; ">
<br>
<h1 style="padding-bottom: 4px;">Deskripsi</h1>
<table >
<tr></tr><td>Progress Rate  menggambarkan persentase diagram berdasarkan persentase progres setiap project dan tasks yang diakumulasi dan dirata-ratakan</td></tr>
<tr></tr><td>Completion Rate menggambarkan prsentase diagram yang diambil berdasarkan critical project dan task yang harus diselesaikan</td></tr>
</table>
</div>

<div id="chart1" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div>
<div id="chart2" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div>
