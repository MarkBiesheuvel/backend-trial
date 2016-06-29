<?php

// Load database connection, helpers, etc.
require_once(__DIR__ . '/errors.php');
require_once(__DIR__ . '/include.php');

// Vars
$period = 12; // Life-Time of 12 months
$commission = 0.10; // 10% commission

// Prepare query
$result = $db
	->prepare('
		SELECT m.first_month, COUNT(m.booker_id) total_bookers, SUM(s.revenue) total_revenue, SUM(s.number_of_bookings) total_number_of_bookings
		FROM (
			SELECT b.booker_id, strftime("%Y-%m", min(i.end_timestamp), "unixepoch") AS first_month
			FROM bookings b
			INNER JOIN bookingitems i ON b.id = i.booking_id
			WHERE i.item_id IN (
				SELECT item_id
				FROM spaces
			)
			GROUP BY b.booker_id
		) AS m
		LEFT JOIN (
			SELECT b.booker_id, SUM(i.locked_total_price) AS revenue, COUNT(b.id) AS number_of_bookings
			FROM bookings b
			INNER JOIN bookingitems i ON b.id = i.booking_id
			WHERE i.item_id IN (
				SELECT item_id
				FROM spaces
			)
			GROUP BY b.booker_id
		) s ON m.booker_id = s.booker_id
		GROUP BY m.first_month
		ORDER BY m.first_month
	')
	->run()
;
?>
<!doctype html>
<html>
	<head>
		<title>Assignment 1: Create a Report (SQL)</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<style type="text/css">
			.report-table
			{
				width: 100%;
				border: 1px solid #000000;
			}
			.report-table td,
			.report-table th
			{
				text-align: left;
				border: 1px solid #000000;
				padding: 5px;
			}
			.report-table .right
			{
				text-align: right;
			}
		</style>
	</head>
	<body>
		<h1>Report:</h1>
		<table class="report-table">
			<thead>
				<tr>
					<th>Start</th>
					<th class="right">Bookers</th>
					<th class="right"># of bookings (avg)</th>
					<th class="right">Turnover (avg)</th>
					<th class="right">LTV</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($result as $index => $row): ?>
					<tr>
						<td><?= $row->first_month ?></td>
						<td class="right"><?= $row->total_bookers ?></td>
						<td class="right"><?= number_format($row->total_number_of_bookings / $row->total_bookers, 1) ?></td>
						<td class="right"><?= currency($row->total_revenue / $row->total_bookers) ?></td>
						<td class="right"><?= currency($row->total_revenue / $row->total_bookers * $commission) ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4" class="right"><strong>Total rows:</strong></td>
					<td><?= $index + 1 ?></td>
				</tr>
			</tfoot>
		</table>
	</body>
</html>