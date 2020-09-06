<?php

require_once(dirname(__FILE__)."/data.php");

function wp_currencies_draw_plot()
{
    try
    {
        $data_extractor = new DataExtractor();
    }
    catch (CurrencyExtractorError $error)
    {
        echo "<h2>WPCurrencies error:</h2>".$error->getMessage()."</h2>";
        return;
    }
?>
        <style>
        .chart {
            display: block;
        }
        .chart-container {
            width: 100%;
        }
        .row {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .row-btn {
            width: 25%;
            padding: 5px;
            border: 1px solid #000;
            margin-bottom: 0;
            font-weight: normal;
			font-size: smaller;
            text-decoration: none;
            cursor: pointer;
        }

        .row-btn.active {
            border-bottom: none;
            font-weight: bold;
            text-decoration: underline;
            padding-bottom: 6px;
        }
        .row-time {
			width: 20%;
			padding: 5px;
			border: 1px solid #000;
			margin-bottom: 0;
			font-weight: normal;
			font-size: smaller;
			text-decoration: none;
			cursor: pointer;
		  }

		  .row-time:first-of-type {
			cursor: initial;
			width: 40%;
		  }

        .row-time.active {
            border-top: none;
            font-weight: bold;
            text-decoration: underline;
            padding-top: 6px;
        }
        </style>
        <div class="chart-container">
      <div id="pairs-row" class="row"></div>
      <canvas class="chart" id="myChart" width="400" height="350"></canvas>
      <div id="time-row" class="row">
        <div id="selectedOptions" class="row-time"></div>
      </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.css"></script>
    <script>
      async function postData(
        url = "<?php echo admin_url("admin-ajax.php") ?>?action=currencies",
        str = ""
      ) {
        const response = await fetch(url, {
          method: "POST",
          body: str,
        });
        const res = await response.json();
        data = res;
        displayReqChart(myChart);
      }
      setInterval(postData, 60 * 1000);

      let selectedPair = "EURUSD";
      let selectedTime = "day";

      let data = <?php $data_extractor->echo_js_pair_data(); ?>;

      const pairsRow = document.getElementById("pairs-row");
      const timeRow = document.getElementById("time-row");

      let pairs = [];
      let times = [];

      Object.keys(data).forEach((key) => {
        pairs.push(key);
      });

      pairs.forEach((key) => {
        pairsRow.innerHTML += `<div class="row-btn">${key}</div>`;
      });

      Object.keys(Object.entries(data)[0][1]).forEach((key) => {
        times.push(key);
      });

      times.forEach((key) => {
        timeRow.innerHTML += `<div class="row-time">${key}</div>`;
      });

      const pairBtns = document.getElementsByClassName("row-btn");
      const timeBtns = document.getElementsByClassName("row-time");

      for (btn of pairBtns) {
        if (btn.innerText === selectedPair) btn.classList.add("active");
      }

      for (btn of timeBtns) {
        if (btn.innerText === selectedTime) btn.classList.add("active");
      }

      var chartOptions = {
        legend: {
          display: false,
          position: "top",
          labels: {
            boxWidth: 80,
            fontColor: "black",
          },
        },
      };

      const chart = document.getElementById("myChart").getContext("2d");

      const myChart = new Chart(chart, {
        type: "line",
        data: {
          labels: [1, 2, 3],
          datasets: [
            {
              borderColor: "#0000FF",
              fill: false,
              lineTension: 0,
              pointHitRadius: 15,
              data: [10, 14, 8, 24, 12],
            },
          ],
        },
        options: chartOptions,
      });

      const displaySelectedPairAndValue = () => {
        let yArr = data[selectedPair][selectedTime].y;
        document.getElementById("selectedOptions").innerHTML = `
            ${selectedPair}: ${yArr[yArr.length - 1]}
          `;
      };
      displaySelectedPairAndValue();

      const deactivateBtns = (arr, id) => {
        for (let i = 0; i < arr.length; i++) {
          if (i === id) {
            arr[i].classList.toggle("active", true);
          } else {
            arr[i].classList.toggle("active", false);
          }
        }
      };

      for (let i = 0; i < pairBtns.length; i++) {
        pairBtns[i].addEventListener("click", function () {
          deactivateBtns(pairBtns, i);
          selectedPair = this.childNodes[0].data;
          displayReqChart(myChart);
          displaySelectedPairAndValue();
        });
      }

      for (let i = 1; i < timeBtns.length; i++) {
        timeBtns[i].addEventListener("click", function () {
          deactivateBtns(timeBtns, i);
          selectedTime = this.childNodes[0].data;
          displayReqChart(myChart);
          displaySelectedPairAndValue();
        });
      }

      const displayReqChart = (chart) => {
        chart.data.labels = data[selectedPair][selectedTime].labels;
        chart.data.datasets[0].data = data[selectedPair][selectedTime].y;
        chart.update();
      };
      displayReqChart(myChart);
    </script>
<?php
}
?>