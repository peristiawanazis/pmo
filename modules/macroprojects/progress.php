<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

require_once DP_BASE_DIR.'/modules/macroprojects/projectstats.php';
GLOBAL $m, $macroprojects, $pstatus;

$stats = array();
$pStats = array();
$i = 0;
foreach($macroprojects as $prj){
	$mproject_id = $prj['macroproject_id'];
	$stats[$i] = getTasksStats($prj);
	$pStats[$i] = getProjectsStats($prj);
	//echo json_encode($prj) .'<br/>';
	//echo json_encode($stats) .'<br/>';
	//echo json_encode($pstatus) .'<br/>';
	//echo json_encode($pStats) .'<br/>';
	$i++;
}
//echo json_encode($pStats);
//$allStats = getProjectsStats(null);
//echo json_encode($allStats) .'<br/>';
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
        var s1 = [2, 6, 7, 10, 5];
        var ticks = ['a', 'b', 'c', 'd' , 'e'];
        var projects = <?php echo json_encode($pStats)?> ;
		var tasks = <?php echo json_encode($stats)?> ;
		var baseUrl = <?php echo "\"{$base}\"" ?> ;
		var s2 = new Array();
		var s3 = new Array();
		var ticks2 = new Array();
		var barColors = new Array();
		var x;
		var urlSet = new Array();
		for(i=0;i<projects.length;i++){
		  x = projects.length-(i+1);
		  s2[i] = parseInt(projects[x].completion_percentage);
		  s3[i] = parseInt(tasks[x].completion_percentage);
		  urlSet[i] = baseUrl + '?m=macroprojects&a=view&macroproject_id=' + projects[x].id;
		  ticks2[i] = '<a href="'+ urlSet[i]+'">' + projects[x].name + '</a>';
		  barColors[i] = '#' + projects[x].color;
		  
		}
		
        plot1 = $.jqplot('chart1', [s2], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
			seriesColors: barColors,
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal',
					varyBarColor: true
				},
				pointLabels: {show: true, formatString: '%d\%'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks2
                },
				xaxis:{
					ticks:[0,100],
					tickOptions:{formatString:'%d\%'}
				}
            },
			title: {
				text : 'Progress Rate by Projects',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
		
		plot3 = $.jqplot('chart2', [s3], {
            // Only animate if we're not using excanvas (not in IE 7 or IE 8)..
            animate: !$.jqplot.use_excanvas,
			seriesColors: barColors,
			seriesDefaults:{
				renderer:$.jqplot.BarRenderer,
				shadowAngle: 135,
				rendererOptions: {
					barDirection: 'horizontal',
					varyBarColor: true
				},
				pointLabels: {show: true, formatString: '%d\%'}
			},
            axes: {
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks2
                },
				xaxis:{
					ticks:[0,100],
					tickOptions:{formatString:'%d\%'}
				}
            },
			title: {
				text : 'Progress Rate by Tasks',
				show : true,
				fontSize : 16,
			},
            highlighter: { show: true }
        });
     
        $('#chart1').bind('jqplotDataClick',
            function (ev, seriesIndex, pointIndex, data) {
                location.href = urlSet[pointIndex];
            }
        );
     
		$(".jqplot-yaxis-tick").css({
			cursor: "pointer",
			zIndex: "1"
		}).click(function(){ });
		
    });
</script>


<div id="chart1" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div>
<div id="chart2" style="margin-top:20px; margin-left:20px; width:400px; height:300px;"></div>