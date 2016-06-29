<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

// Vars
$period = intval(post('period', 12)); // Life-Time of 12 months
$commission = floatval(post('commission', 10)); // 10% commission

// In range
$period = max(0, $period);

$commission = max(0, $commission);
$commission = min(100, $commission);

$periodOptions = [3, 6, 12, 18, 24];

// Prepare query
$result = $db
	->prepare('
		SELECT t.first_month, COUNT(t.booker_id) AS total_bookers,
			SUM(t.revenue) AS total_revenue, SUM(t.number_of_bookings) AS total_number_of_bookings
		FROM (
			SELECT m.booker_id, strftime("%Y-%m", m.first_booking, "unixepoch") AS first_month,
				SUM(i.locked_total_price) AS revenue, COUNT(b.id) AS number_of_bookings
			FROM (
				SELECT b.booker_id, MIN(i.end_timestamp) AS first_booking
				FROM bookings b
				INNER JOIN bookingitems i ON b.id = i.booking_id
				WHERE i.item_id IN (
					SELECT item_id
					FROM spaces
				)
				GROUP BY b.booker_id
			) AS m
			INNER JOIN bookings b ON m.booker_id = b.booker_id
			INNER JOIN bookingitems i ON b.id = i.booking_id
			WHERE 
--			i.item_id IN (
--				SELECT item_id
--				FROM spaces
--			)
--			AND
			DATE(i.end_timestamp, "unixepoch") < DATE(m.first_booking, "unixepoch", "start of month", "+'.$period.' months")
			GROUP BY m.booker_id
		) AS t
		GROUP BY t.first_month
	')
	->run()
;
?>
<!doctype html>
<html>
	<head>
		<title>Assignment 1: Create a Report (SQL)</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
	</head>
	<body>
		<div class="container">

			<h1>Report</h1>

			<form action="?" method="post">
				<div class="row">
					<div class="col-md-4">
						<div class="input-group">
							<span class="input-group-addon">Period</span>
							<select name="period" class="form-control">
								<?php foreach($periodOptions as $option): ?>
									<option value="<?= $option?>" <?= ($period === $option)? 'selected' : '' ?>>
										<?= $option ?> months
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="col-md-4">
						<div class="input-group">
							<span class="input-group-addon">Commision</span>
							<input type="number" class="form-control" name="commission" value="<?= $commission ?>" min="0" max="100" step="any">
							<span class="input-group-addon">%</span>
						</div>
					</div>
					<div class="col-md-4">
						<input type="submit" class="btn btn-success">
					</div>
			</form>

			<table class="table">
				<thead>
					<tr>
						<th>Start</th>
						<th class="text-right">Bookers</th>
						<th class="text-right"># of bookings (avg)</th>
						<th class="text-right">Turnover (avg)</th>
						<th class="text-right">LTV</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($result as $index => $row): ?>
						<tr>
							<td><?= $row->first_month ?></td>
							<td class="text-right"><?= $row->total_bookers ?></td>
							<td class="text-right"><?= number_format($row->total_number_of_bookings / $row->total_bookers, 1) ?></td>
							<td class="text-right"><?= currency($row->total_revenue / $row->total_bookers) ?></td>
							<td class="text-right"><?= currency($row->total_revenue / $row->total_bookers * $commission / 100) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="4" class="text-right"><strong>Total rows:</strong></td>
						<td><?= $index + 1 ?></td>
					</tr>
				</tfoot>
			</table>
		</div>
	</body>
</html>