//https://colorbrewer2.org/?type=diverging&scheme=RdYlGn&n=10
var RdYlGn = [
  "#a50026",
  "#d73027",
  "#f46d43",
  "#fdae61",
  "#fee08b",
  "#d9ef8b",
  "#a6d96a",
  "#66bd63",
  "#1a9850",
  "#006837",
];

function getPCMCellId(row, col) {
  return `c_${row}_${col}`;
}

function calculateMajorityMargin(questions, poll_answers) {
  m = questions.length;
  n = poll_answers.length;
  var count = [...Array(m)].map((_) => Array(m).fill(0)); //count is 2D nxn matrix count[i,j]=0;

  nodesInfo = [...Array(m)].map((el, index) => {
    return {
      index: index,
      simplifiedDogson: 0,
      etsd: 0,
      copeland: 0,
      label: String.fromCharCode(65 + index),
      info: questions[index],
    };
  });

  for (var i = 0; i < n; i++) {
    var ans = poll_answers[i].answers.split(",");
    for (var j = 0; j < ans.length - 1; j++) {
      row_ind = parseInt(ans[j]) - 1;
      for (var k = j + 1; k < ans.length; k++) {
        col_ind = parseInt(ans[k]) - 1;
        count[row_ind][col_ind]++;
      }
    }
  }

  const etsd_const = m * (Math.log(m) + 1);
  for (var i = 0; i < m; i++) {
    for (var j = 0; j < m; j++)
      nodesInfo[i].simplifiedDogson += Math.max(0, n - 2 * count[i][j]);
    nodesInfo[i].etsd = m * nodesInfo[i].simplifiedDogson + etsd_const;
  }

  nodesInfo = nodesInfo.sort((a, b) => a.etsd - b.etsd); //ascending sorting  according to etsd property;
  majorityMargin = [...Array(m)].map((_) => Array(m).fill(0)); //mm is 2D nxn matrix : majority margin;
  maxMM = -poll_answers.length;
  for (var i = 0; i < m; i++) {
    for (var j = 0; j < m; j++) {
      if (i != j) {
        majorityMargin[i][j] = count[i][j] - count[j][i];
        if (majorityMargin[i][j] > maxMM) maxMM = majorityMargin[i][j];
      }
    }
  }
}

function colorizePCM(questions, poll_answers) {
  let n = poll_answers.length;
  let m = questions.length;
  colors = 1.0 * RdYlGn.length;
  calculateMajorityMargin(questions, poll_answers);
  let a = (colors - 1.0) / (2 * maxMM);
  let b = a * maxMM; //quantify color in RdYlGn table;
  for (i = 0; i < m; i++)
    for (j = 0; j < m; j++) {
      let x = majorityMargin[i][j];
      ind = Math.round(a * x + b);
      color = RdYlGn[ind];
      saturation = 100 - Math.trunc(100 * (Math.abs(x) / n));
      cell = getPCMCellId(i + 1, j + 1);
      saturated_color = applySaturationToHexColor(color, saturation);
      //console.log(ind + "," + color + " of " + m[i][j] + " with sat=" + saturation + ", having color:" + saturated_color);
      document.getElementById(cell).style.backgroundColor = saturated_color;
    }
  for (i = 0; i < nodesInfo.length; i++) {
    document.getElementById("qver_" + (i + 1)).innerHTML = nodesInfo[i].label;
    document.getElementById("qhor_" + (i + 1)).innerHTML = nodesInfo[i].label;
  }
}

//code by evpozdniakov at
//https://stackoverflow.com/questions/13806483/increase-or-decrease-color-saturation

function applySaturationToHexColor(hex, saturationPercent) {
  if (!/^#([0-9a-f]{6})$/i.test(hex)) {
    throw "Unexpected color format";
  }

  if (saturationPercent < 0 || saturationPercent > 100) {
    throw "Unexpected color format";
  }

  var saturationFloat = saturationPercent / 100,
    rgbIntensityFloat = [
      parseInt(hex.substr(1, 2), 16) / 255,
      parseInt(hex.substr(3, 2), 16) / 255,
      parseInt(hex.substr(5, 2), 16) / 255,
    ];

  var rgbIntensityFloatSorted = rgbIntensityFloat
      .slice(0)
      .sort(function (a, b) {
        return a - b;
      }),
    maxIntensityFloat = rgbIntensityFloatSorted[2],
    mediumIntensityFloat = rgbIntensityFloatSorted[1],
    minIntensityFloat = rgbIntensityFloatSorted[0];

  if (maxIntensityFloat == minIntensityFloat) {
    // All colors have same intensity, which means
    // the original color is gray, so we can't change saturation.
    return hex;
  }

  // New color max intensity wont change. Lets find medium and weak intensities.
  var newMediumIntensityFloat,
    newMinIntensityFloat = maxIntensityFloat * (1 - saturationFloat);

  if (mediumIntensityFloat == minIntensityFloat) {
    // Weak colors have equal intensity.
    newMediumIntensityFloat = newMinIntensityFloat;
  } else {
    // Calculate medium intensity with respect to original intensity proportion.
    var intensityProportion =
      (maxIntensityFloat - mediumIntensityFloat) /
      (mediumIntensityFloat - minIntensityFloat);
    newMediumIntensityFloat =
      (intensityProportion * newMinIntensityFloat + maxIntensityFloat) /
      (intensityProportion + 1);
  }

  var newRgbIntensityFloat = [],
    newRgbIntensityFloatSorted = [
      newMinIntensityFloat,
      newMediumIntensityFloat,
      maxIntensityFloat,
    ];

  // We've found new intensities, but we have then sorted from min to max.
  // Now we have to restore original order.
  rgbIntensityFloat.forEach(function (originalRgb) {
    var rgbSortedIndex = rgbIntensityFloatSorted.indexOf(originalRgb);
    newRgbIntensityFloat.push(newRgbIntensityFloatSorted[rgbSortedIndex]);
  });

  var floatToHex = function (val) {
      return ("0" + Math.round(val * 255).toString(16)).substr(-2);
    },
    rgb2hex = function (rgb) {
      return "#" + floatToHex(rgb[0]) + floatToHex(rgb[1]) + floatToHex(rgb[2]);
    };

  var newHex = rgb2hex(newRgbIntensityFloat);

  return newHex;
}
