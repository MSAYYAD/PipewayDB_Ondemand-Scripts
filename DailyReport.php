<?php
// Date  : 21 May 2021 05:04 PM

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '-1');
//date_default_timezone_set('America/New_York');
date_default_timezone_set('US/Eastern');
include_once("PHP_XLSXWriter/xlsxwriter.class.php");
include_once("TCPDF/tcpdf.php");
include_once('PHPMailer/PHPMailerAutoload.php');


class DailyReport
{

	private function dbConnection()
	{
		// $this->pdo = new PDO('mysql:dbname=wobot_prod;host=localhost', 'root', 'nish',  array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION)) or die('Connection fail.');
		$this->pdo = new PDO('mysql:dbname=aaca_live;host=192.168.13.167', 'admin167', 'Aaca@123#',  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)) or die('Connection fail.');
	}






	public function start()
	{

		$this->dbConnection();
		$pdo = $this->pdo;

		$current_date = date('Y-m-d');
		$day = date('l', strtotime($current_date));

		if ($day == 'Monday') {
			$day = " AND FIND_IN_SET('1',Day)";
		} else if ($day == 'Tuesday') {
			$day = " AND FIND_IN_SET('2',Day)";
		} else if ($day == 'Wednesday') {
			$day = " AND FIND_IN_SET('3',Day)";
		} else if ($day == 'Thursday') {
			$day = " AND FIND_IN_SET('4',Day)";
		} else if ($day == 'Friday') {
			$day = " AND FIND_IN_SET('5',Day)";
		} else if ($day == 'Saturday') {
			$day = " AND FIND_IN_SET('6',Day)";
		} else if ($day == 'Sunday') {
			$day = " AND FIND_IN_SET('7',Day)";
		}

		if (date("h:i a") == '12:00 am') {
			exit();
		}

		$this->dailyReportDetails();


		// Daily if EndDate is null
		$statement = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Daily' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 ";


		$query = $pdo->prepare($statement);
		$query->execute();
		$rows = $query->fetchAll();


		if (count($rows) != 0) {

			foreach ($rows as $row) {

				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';

					$this->upload_daily_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}


		// Daily if both StartDate and EndDate are available
		$statement2 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Daily' AND IsDelete = 0 AND schedule_status = 0 AND status = 0";


		$query2 = $pdo->prepare($statement2);
		$query2->execute();
		$rows2 = $query2->fetchAll();


		if (count($rows2) != 0) {

			foreach ($rows2 as $row) {

				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];


				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';

					$this->upload_daily_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		// Weekly if EndDate is null
		$statement3 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Weekly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $day;


		$query3 = $pdo->prepare($statement3);
		$query3->execute();
		$rows3 = $query3->fetchAll();


		if (count($rows3) != 0) {

			foreach ($rows3 as $row) {

				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];



				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';

					$this->upload_weekly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		// Weekly if both StartDate and EndDate are available
		$statement4 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Weekly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0" . $day;


		$query4 = $pdo->prepare($statement4);
		$query4->execute();
		$rows4 = $query4->fetchAll();


		if (count($rows4) != 0) {

			foreach ($rows4 as $row) {

				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';

					$this->upload_weekly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}


		$current_month = $this->fetchMonths($current_date);

		// Monthly if EndDate is null
		$statement5 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Monthly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0";

		$query5 = $pdo->prepare($statement5);
		$query5->execute();
		$rows5 = $query5->fetchAll();

		if (count($rows5) != 0) {
			foreach ($rows5 as $row) {

				$total_days = $row['monthly_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];


				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		// Monthly if both StartDate and EndDate are available
		$statement6 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Monthly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 ";

		$query6 = $pdo->prepare($statement6);
		$query6->execute();
		$rows6 = $query6->fetchAll();

		if (count($rows6) != 0) {
			foreach ($rows6 as $row) {

				$total_days = $row['monthly_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		$current_month = $this->fetchMonths($current_date);

		//Quarterly if EndDate is null
		$statement7 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Quarterly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0 " . $current_month;

		$query7 = $pdo->prepare($statement7);
		$query7->execute();
		$rows7 = $query7->fetchAll();

		if (count($rows7) != 0) {
			foreach ($rows7 as $row) {

				$total_days = $row['quarterly_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		//Quarterly if both StartDate and EndDate are available
		$statement8 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Quarterly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query8 = $pdo->prepare($statement8);
		$query8->execute();
		$rows8 = $query8->fetchAll();

		if (count($rows8) != 0) {
			foreach ($rows8 as $row) {

				$total_days = $row['quarterly_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}


		//Semi Monthly if EndDate is null
		$statement9 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'semi_monthly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0 " . $current_month;

		$query9 = $pdo->prepare($statement9);
		$query9->execute();
		$rows9 = $query9->fetchAll();

		if (count($rows9) != 0) {
			foreach ($rows9 as $row) {

				$total_days = $row['semi_month_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		//Semi Monthly if both StartDate and EndDate are available
		$statement10 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'semi_monthly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query10 = $pdo->prepare($statement10);
		$query10->execute();
		$rows10 = $query10->fetchAll();

		if (count($rows10) != 0) {
			foreach ($rows10 as $row) {

				$total_days = $row['semi_month_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}


		//Annually if EndDate is null
		$statement11 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Annually' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query11 = $pdo->prepare($statement11);
		$query11->execute();
		$rows11 = $query11->fetchAll();

		if (count($rows11) != 0) {
			foreach ($rows11 as $row) {

				$total_days = $row['annually_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];


				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		//Annually if both StartDate and EndDate are available
		$statement12 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Annually' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query12 = $pdo->prepare($statement12);
		$query12->execute();
		$rows12 = $query12->fetchAll();

		if (count($rows12) != 0) {
			foreach ($rows12 as $row) {

				$total_days = $row['annually_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];


				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}


		//Customization if EndDate is null
		$statement13 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Customization' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 AND Time = '" . date("H:i") . "'";

		$query13 = $pdo->prepare($statement13);
		$query13->execute();
		$rows13 = $query13->fetchAll();

		if (count($rows13) != 0) {
			foreach ($rows13 as $row) {

				$total_days = $row['no_of_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$custom_start_date = $row['custom_start_date'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];


				if ($fileType == 'excel') {
					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';

					$this->fetchBusinessDay($total_days, $id, $path, $reportName, $custom_start_date, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		//Customization if both StartDate and EndDate are available
		$statement14 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Customization' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 AND Time = '" . date("H:i") . "'";

		$query14 = $pdo->prepare($statement14);
		$query14->execute();
		$rows14 = $query14->fetchAll();

		if (count($rows14) != 0) {

			foreach ($rows14 as $row) {

				$total_days = $row['no_of_days'];
				$path = $row['directoryPath'];
				$id = $row['BatchId'];
				$custom_start_date = $row['custom_start_date'];
				$reportName = $row['ReportName'];
				$fileType = $row['fileType'];
				$code_name = $row['code_name'];
				$userType = $row['UserType'];
				$userReportName = $row['user_report_name'];
				$outputName = $row['output_name'];
				$reportDescription = $row['report_description'];
				$mailNotification = $row['mail_notification'];
				$sftpId = $row['sftp_id'];

				if ($fileType == 'excel') {

					$outputName = str_replace(" ", "_", $outputName) . '_' . date('Ymd') . '.xlsx';
					$this->fetchBusinessDay($total_days, $id, $path, $reportName, $custom_start_date, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		}

		// $this->dailyReportMail();


		// Changing Job status at 12:00 am on everyday aac. to USA timezone. 
		$time = date("h:i a");

		if ($time == '11:59 pm') {
			$this->update_report_status();
		}

		$this->compareDailyReport();
		exit();
	}


	public function compareDailyReport()
	{
		$this->dbConnection();
		$pdo = $this->pdo;

		$current_date = date("Y-m-d");

		$statement = "SELECT batchId FROM SCHEDULER_DAILY_REPORT_LOGS WHERE createdAt = '" . $current_date . "' ";

		$query = $pdo->prepare($statement);
		$query->execute();
		$rows = $query->fetchAll();

		foreach ($rows as $row) {

			$ids[] = $row['batchId'];
		}

		$str = implode(',', $ids);

		$statement2 = "SELECT * FROM Batch_Report WHERE BatchId IN ($str) AND (SELECT count(1) FROM Batch_Report WHERE BatchId IN ($str)) = (SELECT count(1) FROM Batch_Report WHERE BatchId IN ($str) AND (status = 2 OR status = 3))";

		$query2 = $pdo->prepare($statement2);
		$query2->execute();
		$rows2 = $query2->fetchAll();

		if (count($rows2) != 0) {
			$this->dailyReportMail();
		}
	}


	public function dailyReportDetails()
	{
		$this->dbConnection();
		$pdo = $this->pdo;

		$current_date = date('Y-m-d');
		$day = date('l', strtotime($current_date));

		if ($day == 'Monday') {
			$day = " AND FIND_IN_SET('1',Day)";
		} else if ($day == 'Tuesday') {
			$day = " AND FIND_IN_SET('2',Day)";
		} else if ($day == 'Wednesday') {
			$day = " AND FIND_IN_SET('3',Day)";
		} else if ($day == 'Thursday') {
			$day = " AND FIND_IN_SET('4',Day)";
		} else if ($day == 'Friday') {
			$day = " AND FIND_IN_SET('5',Day)";
		} else if ($day == 'Saturday') {
			$day = " AND FIND_IN_SET('6',Day)";
		} else if ($day == 'Sunday') {
			$day = " AND FIND_IN_SET('7',Day)";
		}

		// Daily if EndDate is null
		$statement = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Daily' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 ";


		$query = $pdo->prepare($statement);
		$query->execute();
		$rows = $query->fetchAll();


		if (count($rows) != 0) {

			foreach ($rows as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];

				$this->insertReportData($id, $reportName);
			}
		}



		// Daily if both StartDate and EndDate are available
		$statement2 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Daily' AND IsDelete = 0 AND schedule_status = 0 AND status = 0";


		$query2 = $pdo->prepare($statement2);
		$query2->execute();
		$rows2 = $query2->fetchAll();


		if (count($rows2) != 0) {

			foreach ($rows2 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];


				$this->insertReportData($id, $reportName);
			}
		}


		// Weekly if EndDate is null
		$statement3 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Weekly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $day;


		$query3 = $pdo->prepare($statement3);
		$query3->execute();
		$rows3 = $query3->fetchAll();


		if (count($rows3) != 0) {

			foreach ($rows3 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];

				$this->insertReportData($id, $reportName);
			}
		}

		// Weekly if both StartDate and EndDate are available
		$statement4 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Weekly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0" . $day;


		$query4 = $pdo->prepare($statement4);
		$query4->execute();
		$rows4 = $query4->fetchAll();


		if (count($rows4) != 0) {

			foreach ($rows4 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];



				$this->insertReportData($id, $reportName);
			}
		}


		$current_month = $this->fetchMonths($current_date);

		// Monthly if EndDate is null
		$statement5 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Monthly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0";

		$query5 = $pdo->prepare($statement5);
		$query5->execute();
		$rows5 = $query5->fetchAll();

		if (count($rows5) != 0) {
			foreach ($rows5 as $row) {



				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['monthly_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}

		// Monthly if both StartDate and EndDate are available
		$statement6 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Monthly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 ";

		$query6 = $pdo->prepare($statement6);
		$query6->execute();
		$rows6 = $query6->fetchAll();

		if (count($rows6) != 0) {
			foreach ($rows6 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['monthly_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}

		$current_month = $this->fetchMonths($current_date);

		//Quarterly if EndDate is null
		$statement7 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Quarterly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0 " . $current_month;

		$query7 = $pdo->prepare($statement7);
		$query7->execute();
		$rows7 = $query7->fetchAll();

		if (count($rows7) != 0) {
			foreach ($rows7 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['quarterly_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}

		//Quarterly if both StartDate and EndDate are available
		$statement8 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Quarterly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query8 = $pdo->prepare($statement8);
		$query8->execute();
		$rows8 = $query8->fetchAll();

		if (count($rows8) != 0) {
			foreach ($rows8 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['quarterly_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}


		//Semi Monthly if EndDate is null
		$statement9 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'semi_monthly' AND IsDelete = 0 AND status = 0 AND schedule_status = 0 " . $current_month;

		$query9 = $pdo->prepare($statement9);
		$query9->execute();
		$rows9 = $query9->fetchAll();

		if (count($rows9) != 0) {
			foreach ($rows9 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['semi_month_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}

		//Semi Monthly if both StartDate and EndDate are available
		$statement10 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'semi_monthly' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query10 = $pdo->prepare($statement10);
		$query10->execute();
		$rows10 = $query10->fetchAll();

		if (count($rows10) != 0) {
			foreach ($rows10 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['semi_month_days'];

				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}


		//Annually if EndDate is null
		$statement11 = "SELECT * FROM Batch_Report WHERE EndDate = '' AND recurrence_pattern = 'Annually' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query11 = $pdo->prepare($statement11);
		$query11->execute();
		$rows11 = $query11->fetchAll();

		if (count($rows11) != 0) {
			foreach ($rows11 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['annually_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}

		//Annually if both StartDate and EndDate are available
		$statement12 = "SELECT * FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND recurrence_pattern = 'Annually' AND IsDelete = 0 AND schedule_status = 0 AND status = 0 " . $current_month;

		$query12 = $pdo->prepare($statement12);
		$query12->execute();
		$rows12 = $query12->fetchAll();

		if (count($rows12) != 0) {
			foreach ($rows12 as $row) {


				$id = $row['BatchId'];
				$reportName = $row['ReportName'];
				$total_days = $row['annually_days'];


				$this->fetchDayNew($total_days, $id, $reportName);
			}
		}
	}

	public function insertReportData($id, $report_name)
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$current_date = date("Y-m-d");
		$report_end_time = date("H:i:s");

		$statement = "SELECT * FROM SCHEDULER_DAILY_REPORT_LOGS WHERE batchId = '" . $id . "' AND createdAt = '" . $current_date . "' ";

		$query = $pdo->prepare($statement);
		$query->execute();
		// $query->closeCursor();
		$rows = $query->fetchColumn();

		if ($rows == 0) {
			$insert_logs = "INSERT INTO `SCHEDULER_DAILY_REPORT_LOGS` (reportName,batchId,createdAt) VALUES ('" . $report_name . "','" . $id . "','" . $current_date . "') ";

			$log_query = $pdo->prepare($insert_logs);
			$log_query->execute();
			$log_query->closeCursor();
		}
	}

	public function upload_daily_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		try {

			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;


			if ($reportName == 'Remitting Manual Entry') {
				$output = $this->remittingManualEntry($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			} else {
				$output = $this->billMeLaterCollectionReport($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			}



			// Changing the status after completion of job
			// $update_query2 = "UPDATE `Batch_Report` SET status = 2, last_run_date = '".date("Y-m-d H:i:s")."' WHERE BatchId ='".$id."' ";
			//    $result2 = $pdo->prepare($update_query2);
			//    $result2->execute();
			//    $report_end_time = date("H:i:s");

			// $this->scheduler_logs($status,$reportName,$report_start_time,$report_end_time);

			$status_msg = $output['status_msg'];

			$result->status = 1;
			echo $result->message = $status_msg . ' ' . date("Y-m-d H:i:s") . " ", "\n";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}



	public function remittingManualEntry($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		try {

			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;

			$report_start_time = date("H:i:s");

			// Changing the job status from schedular to In Execution.
			$update_query = "UPDATE `Batch_Report` SET status = 1 WHERE BatchId ='" . $id . "' ";
			$result = $pdo->prepare($update_query);
			$result->execute();


			//   $statement = 'SELECT * FROM scheduleReports WHERE report_type = "'.$reportName.'" ';

			// $new_query = $pdo->prepare($statement);
			// $new_query->execute();  
			// $rows_data = $new_query->fetchAll();


			if ($userType == 1) {
				$fetchQuery = $pdo->prepare("CALL RemittingManualEntry()");
				$fetchQuery->execute();

				$rows = $fetchQuery->rowCount();

				if ($rows != 0) {

					$d = new DateTime();

					$current_date = $d->format("m-d-Y H:i:s.v");

					$reportName = str_replace(' ', '_', $reportName);

					$filename = $reportName . "(" . $current_date . ").xlsx";

					header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
					header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
					header('Content-Transfer-Encoding: binary');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');

					$header = array(
						"VENDORNUM" => "string",
						"RMSASNDESC" => "string",
						"CUROFFCRCD" => "string",
						"Manual,Cost" => "integer",
						"Auto,Cost" => "integer",
						"Total,Cost" => "integer",
						"PCT_MAN_COST" => "dollar",
						"Manual,Remit" => "integer",
						"Auto,Remit" => "integer",
						"Total,Remit" => "integer",
						"PCT_MAN_REMIT" => "dollar",
					);

					$style = array(
						'font-style' => 'bold',
						'fill' => '#eee',
						'halign' => 'center',
						'border' => 'left,right,top,bottom',
						'widths' => [25, 35, 25, 25, 25, 25, 25, 25, 25, 25, 25]
					);

					$sheetName = 'RemittingManualEntry' . '_' . date('m-d-Y');

					$writer = new XLSXWriter();
					$writer->writeSheetHeader($sheetName, $header, $style);

					while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

						$writer->writeSheetRow($sheetName, $row);
					};

					$fetchQuery->closeCursor();


					if (!is_dir('/var/www/html/bi/dist/' . $path)) {
						mkdir('/var/www/html/bi/dist/' . $path, 0777, true);
						chmod('/var/www/html/bi/dist/' . $path, 0777);
					}



					$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $path . '/' . $filename . '', __FILE__));

					if (!file_exists('/var/www/html/bi/dist/' . $path . '/' . $filename)) {
						$status = 0;
					} else {
						$status = 1;
					}
					$new_row = 'ALL';
					$new_status = 2;
				} else {
					$new_row = 'ALL';
					$new_status = 3;
					// $update_new_query = "UPDATE `Batch_Report` SET status = 3 WHERE BatchId ='".$id."' ";
					//    $result = $pdo->prepare($update_new_query);
					//    $result->execute();
				}


				// For SMTP Report
				if ($sftpId != 0) {
					sleep(10);
					$sftp = $this->sftpCredentials($sftpId, $path, $filename, $new_row, $status);

					$msg = $sftp['msg'];
					$status = $sftp['status'];
					$status_msg = $sftp['status_msg'];
					$sftpStatus = 1;

					$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
				}


				// Mail Notification
				if ($mailNotification == 1) {

					$split = explode("/", $path);

					$fileName = $split[count($split) - 1];


					$folderName = $this->getFolderName(trim($fileName));
					$new_row = 'AACA';
					$emailId = $this->getEmailId($new_row, $userType, $folderName, $path);

					foreach ($emailId as $email) {
						if ($rows != 0) {
							$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
						}
					}
				}

				if ($sftpId == 0) {

					if ($new_status == 3) {
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
					} else {
						$msg = 'Report generated successfully';
						$status_msg = 'generated';
						$sftpStatus = 0;
					}

					$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
				}
			}

			if ($userType == 2) {

				$firm_code = explode(",", $code_name);

				$i = 0;
				foreach ($firm_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL RemittingManualEntry()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"VENDORNUM" => "string",
							"RMSASNDESC" => "string",
							"CUROFFCRCD" => "string",
							"Manual,Cost" => "integer",
							"Auto,Cost" => "integer",
							"Total,Cost" => "integer",
							"PCT_MAN_COST" => "dollar",
							"Manual,Remit" => "integer",
							"Auto,Remit" => "integer",
							"Total,Remit" => "integer",
							"PCT_MAN_REMIT" => "dollar",
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 35, 25, 25, 25, 25, 25, 25, 25, 25, 25]
						);

						$sheetName = 'RemittingManualEntry' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$i])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$i], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$i], 0777);
						}



						$checkCompanyStatus = $pdo->prepare("SELECT * FROM `CC_REGSTR` WHERE UTYPE = 2 AND Isdeleted = 0 AND CCODE = '" . $new_row . "'");
						$checkCompanyStatus->execute();
						$data = $checkCompanyStatus->fetchAll();
						$cmpStatus = $data[0]['CSTATUS'];
						$checkCompanyStatus->closeCursor();

						if ($cmpStatus != 4) {

							$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$i] . '/' . $filename . '', __FILE__));

							if (!file_exists('/var/www/html/bi/dist/' . $paths[$i] . '/' . $filename)) {
								$status = 0;
							} else {
								$status = 1;
							}
						} else {
							$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/MAKO/downloadfile/AACA/CP/' . $filename . '', __FILE__));

							if (!file_exists('/var/www/html/bi/dist/MAKO/downloadfile/AACA/CP/' . $filename)) {
								$status = 0;
							} else {
								$status = 1;
							}
						}

						$new_status = 2;

						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$i], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}


						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$i]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));

							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$i]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}
					} else {

						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$i++;
				}
			}

			if ($userType == 3) {

				$client_code = explode(",", $code_name);

				$j = 0;
				foreach ($client_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL RemittingManualEntry()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"VENDORNUM" => "string",
							"RMSASNDESC" => "string",
							"CUROFFCRCD" => "string",
							"Manual,Cost" => "integer",
							"Auto,Cost" => "integer",
							"Total,Cost" => "integer",
							"PCT_MAN_COST" => "dollar",
							"Manual,Remit" => "integer",
							"Auto,Remit" => "integer",
							"Total,Remit" => "integer",
							"PCT_MAN_REMIT" => "dollar",
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 35, 25, 25, 25, 25, 25, 25, 25, 25, 25]
						);

						$sheetName = 'RemittingManualEntry' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$j])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$j], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$j], 0777);
						}



						$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$j] . '/' . $filename . '', __FILE__));

						if (!file_exists('/var/www/html/bi/dist/' . $paths[$j] . '/' . $filename)) {
							$status = 0;
						} else {
							$status = 1;
						}

						$new_status = 2;

						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$j], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}

						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$j]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));
							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$j]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}
					} else {

						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$j++;
				}
			}


			if ($userType == 4) {

				$agency_code = explode(",", $code_name);

				$k = 0;
				foreach ($agency_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL RemittingManualEntry()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"VENDORNUM" => "string",
							"RMSASNDESC" => "string",
							"CUROFFCRCD" => "string",
							"Manual,Cost" => "integer",
							"Auto,Cost" => "integer",
							"Total,Cost" => "integer",
							"PCT_MAN_COST" => "dollar",
							"Manual,Remit" => "integer",
							"Auto,Remit" => "integer",
							"Total,Remit" => "integer",
							"PCT_MAN_REMIT" => "dollar",
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 35, 25, 25, 25, 25, 25, 25, 25, 25, 25]
						);

						$sheetName = 'RemittingManualEntry' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$k])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$k], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$k], 0777);
						}



						$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$k] . '/' . $filename . '', __FILE__));

						if (!file_exists('/var/www/html/bi/dist/' . $paths[$k] . '/' . $filename)) {
							$status = 0;
						} else {
							$status = 1;
						}

						$new_status = 2;

						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$k], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sfptStatus);
						}

						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$k]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));
							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$k]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sfptStatus);
						}
					} else {

						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$k++;
				}
			}


			// $writer->writeToStdOut();


			// Changing the status after completion of job
			$update_query2 = "UPDATE `Batch_Report` SET status = '" . $new_status . "', last_run_date = '" . date("Y-m-d H:i:s") . "' WHERE BatchId ='" . $id . "' ";
			$result2 = $pdo->prepare($update_query2);
			$result2->execute();
			$result2->closeCursor();
			$report_end_time = date("H:i:s");

			// $this->scheduler_logs($status,$reportName,$report_start_time,$report_end_time);

			return array('status' => $status, 'status_msg' => $status_msg);

			// $result->status = 1;
			//  echo $result->message = "Excel Successfully Created ".date("Y-m-d H:i:s")." ", "\n";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}


	public function billMeLaterCollectionReport($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		try {


			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;

			$report_start_time = date("H:i:s");

			// Changing the job status from schedular to In Execution.
			$update_query = "UPDATE `Batch_Report` SET status = 1 WHERE BatchId ='" . $id . "' ";
			$result = $pdo->prepare($update_query);
			$result->execute();

			if ($userType == 1) {
				$fetchQuery = $pdo->prepare("CALL BMLCollectionReport()");
				$fetchQuery->execute();

				$rows = $fetchQuery->rowCount();

				if ($rows != 0) {

					$d = new DateTime();

					$current_date = $d->format("m-d-Y H:i:s.v");

					$reportName = str_replace(' ', '_', $reportName);

					$filename = $reportName . "(" . $current_date . ").xlsx";

					header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
					header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
					header('Content-Transfer-Encoding: binary');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');

					$header = array(
						"COLLTYPE" => "string",
						"Collection Amount" => "integer"
					);

					$style = array(
						'font-style' => 'bold',
						'fill' => '#eee',
						'halign' => 'center',
						'border' => 'left,right,top,bottom',
						'widths' => [25, 25]
					);

					$sheetName = 'BMLCollectionReport' . '_' . date('m-d-Y');

					$writer = new XLSXWriter();
					$writer->writeSheetHeader($sheetName, $header, $style);

					while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

						$writer->writeSheetRow($sheetName, $row);
					};

					$fetchQuery->closeCursor();


					if (!is_dir('/var/www/html/bi/dist/' . $path)) {
						mkdir('/var/www/html/bi/dist/' . $path, 0777, true);
						chmod('/var/www/html/bi/dist/' . $path, 0777);
					}



					$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $path . '/' . $filename . '', __FILE__));

					if (!file_exists('/var/www/html/bi/dist/' . $path . '/' . $filename)) {
						$status = 0;
					} else {
						$status = 1;
					}

					$new_row = 'ALL';
					$new_status = 2;
				} else {
					$new_row = 'ALL';
					$new_status = 3;
					$update_new_query = "UPDATE `Batch_Report` SET status = 3 WHERE BatchId ='" . $id . "' ";
					$result = $pdo->prepare($update_new_query);
					$result->execute();
				}

				// For SMTP Report
				if ($sftpId != 0) {
					sleep(10);
					$sftp = $this->sftpCredentials($sftpId, $path, $filename, $new_row, $status);

					$msg = $sftp['msg'];
					$status = $sftp['status'];
					$status_msg = $sftp['status_msg'];
					$sftpStatus = 1;

					$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
				}

				// Mail Notification
				if ($mailNotification == 1) {

					$split = explode("/", $path);

					$fileName = $split[count($split) - 1];


					$folderName = $this->getFolderName(trim($fileName));
					$emailId = $this->getEmailId($new_row, $userType, $folderName, $path);

					foreach ($emailId as $email) {
						if ($rows != 0) {
							$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
						}
					}
				}

				if ($sftpId == 0) {
					if ($new_status == 3) {
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
					} else {
						$msg = 'Report generated successfully';
						$status_msg = 'generated';
						$sftpStatus = 0;
					}

					$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
				}
			}


			if ($userType == 2) {

				$firm_code = explode(",", $code_name);

				$i = 0;
				foreach ($firm_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL BMLCollectionReport()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"COLLTYPE" => "string",
							"Collection Amount" => "integer"
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 25]
						);

						$sheetName = 'BMLCollectionReport' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$i])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$i], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$i], 0777);
						}

						$checkCompanyStatus = $pdo->prepare("SELECT * FROM `CC_REGSTR` WHERE UTYPE = 2 AND Isdeleted = 0 AND CCODE = '" . $new_row . "'");
						$checkCompanyStatus->execute();
						$data = $checkCompanyStatus->fetchAll();

						$cmpStatus = $data[0]['CSTATUS'];
						$checkCompanyStatus->closeCursor();

						if ($cmpStatus != 4) {

							$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$i] . '/' . $filename . '', __FILE__));

							if (!file_exists('/var/www/html/bi/dist/' . $paths[$i] . '/' . $filename)) {
								$status = 0;
							} else {
								$status = 1;
							}
						} else {
							$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/MAKO/downloadfile/AACA/CP/' . $filename . '', __FILE__));

							if (!file_exists('/var/www/html/bi/dist/MAKO/downloadfile/AACA/CP/' . $filename)) {
								$status = 0;
							} else {
								$status = 1;
							}
						}

						$new_status = 2;



						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$i], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}

						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$i]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));
							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$i]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}
					} else {
						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$i++;
				}
			}



			if ($userType == 3) {

				$client_code = explode(",", $code_name);

				$j = 0;
				foreach ($client_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL BMLCollectionReport()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"COLLTYPE" => "string",
							"Collection Amount" => "integer"
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 25]
						);

						$sheetName = 'BMLCollectionReport' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$j])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$j], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$j], 0777);
						}



						$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$j] . '/' . $filename . '', __FILE__));

						if (!file_exists('/var/www/html/bi/dist/' . $paths[$j] . '/' . $filename)) {
							$status = 0;
						} else {
							$status = 1;
						}
						$new_status = 2;

						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$j], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}

						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$j]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));
							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$j]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}
					} else {
						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$j++;
				}
			}


			if ($userType == 4) {

				$agency_code = explode(",", $code_name);

				$k = 0;
				foreach ($agency_code as $new_row) {
					$fetchQuery = $pdo->prepare("CALL BMLCollectionReport()");

					$fetchQuery->execute();


					$rows = $fetchQuery->rowCount();


					if ($rows != 0) {
						$d = new DateTime();

						$current_date = $d->format("m-d-Y H:i:s.v");

						$reportName = str_replace(' ', '_', $reportName);

						$filename = $reportName . "(" . $current_date . ").xlsx";

						header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
						header('Content-Type: application/openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Transfer-Encoding: binary');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');

						$header = array(
							"COLLTYPE" => "string",
							"Collection Amount" => "integer"
						);

						$style = array(
							'font-style' => 'bold',
							'fill' => '#eee',
							'halign' => 'center',
							'border' => 'left,right,top,bottom',
							'widths' => [25, 25]
						);

						$sheetName = 'BMLCollectionReport' . '_' . date('m-d-Y');

						$writer = new XLSXWriter();
						$writer->writeSheetHeader($sheetName, $header, $style);

						while ($row = $fetchQuery->fetch(PDO::FETCH_ASSOC)) {

							$writer->writeSheetRow($sheetName, $row);
						};

						$fetchQuery->closeCursor();

						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$k])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$k], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$k], 0777);
						}



						$writer->writeToFile(str_replace(__FILE__, '/var/www/html/bi/dist/' . $paths[$k] . '/' . $filename . '', __FILE__));

						if (!file_exists('/var/www/html/bi/dist/' . $paths[$k] . '/' . $filename)) {
							$status = 0;
						} else {
							$status = 1;
						}
						$new_status = 2;

						// For SMTP Report
						if ($sftpId != 0) {
							sleep(10);
							$sftp = $this->sftpCredentials($sftpId, $paths[$k], $filename, $new_row, $status);

							$msg = $sftp['msg'];
							$status = $sftp['status'];
							$status_msg = $sftp['status_msg'];
							$sftpStatus = 1;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}

						// Mail Notification
						if ($mailNotification == 1) {

							$split = explode("/", $paths[$k]);

							$fileName = $split[count($split) - 1];


							$folderName = $this->getFolderName(trim($fileName));
							$emailId = $this->getEmailId($new_row, $userType, $folderName, $paths[$k]);

							foreach ($emailId as $email) {
								if ($rows != 0) {
									$this->new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email);
								}
							}
						}

						if ($sftpId == 0) {

							$msg = 'Report generated successfully';
							$status_msg = 'generated';
							$sftpStatus = 0;

							$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
						}
					} else {
						$new_status = 3;
						$msg = 'No Data available';
						$status_msg = 'failed';
						$sftpStatus = 0;
						$this->scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus);
					}

					$k++;
				}
			}

			$update_query2 = "UPDATE `Batch_Report` SET status = '" . $new_status . "', last_run_date = '" . date("Y-m-d H:i:s") . "' WHERE BatchId ='" . $id . "' ";
			$result2 = $pdo->prepare($update_query2);
			$result2->execute();
			$result2->closeCursor();
			$report_end_time = date("H:i:s");

			return array('status' => $status, 'status_msg' => $status_msg);

			// echo $result->message = $msg.' '.date("Y-m-d H:i:s")." ", "\n";

		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}



	public function upload_weekly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		try {


			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;

			$report_start_time = date("H:i:s");

			// Changing the job status from schedular to In Execution.
			$update_query = "UPDATE `Batch_Report` SET status = 1 WHERE BatchId ='" . $id . "' ";
			$result = $pdo->prepare($update_query);
			$result->execute();



			if ($reportName == 'Remitting Manual Entry') {
				$output = $this->remittingManualEntry($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			} else {
				$output = $this->billMeLaterCollectionReport($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			}



			// Changing the status after completion of job
			// $update_query2 = "UPDATE `Batch_Report` SET status = 2, last_run_date = '".date("Y-m-d H:i:s")."' WHERE BatchId ='".$id."' ";
			//    $result2 = $pdo->prepare($update_query2);
			//    $result2->execute();
			//    $report_end_time = date("H:i:s");

			//          $this->scheduler_logs($status,$reportName,$report_start_time,$report_end_time);

			$status_msg = $output['status_msg'];

			$result->status = 1;
			echo $result->message = $status_msg . ' ' . date("Y-m-d H:i:s") . " ", "\n";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}

	public function upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{

		try {


			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;

			$report_start_time = date("H:i:s");

			// Changing the job status from schedular to In Execution.
			$update_query = "UPDATE `Batch_Report` SET status = 1 WHERE BatchId ='" . $id . "' ";
			$result = $pdo->prepare($update_query);
			$result->execute();



			if ($reportName == 'Remitting Manual Entry') {
				$output = $this->remittingManualEntry($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			} else {
				$output = $this->billMeLaterCollectionReport($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			}

			// Changing the status after completion of job
			// $update_query2 = "UPDATE `Batch_Report` SET status = 2, last_run_date = '".date("Y-m-d H:i:s")."' WHERE BatchId ='".$id."' ";
			//    $result2 = $pdo->prepare($update_query2);
			//    $result2->execute();

			$status_msg = $output['status_msg'];

			$result->status = 1;
			echo $result->message = $status_msg . ' ' . date("Y-m-d H:i:s") . " ", "\n";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}

	public function upload_daily_pdf_report($path, $id, $reportName, $code_name, $userType)
	{
		try {
			$result = new stdClass();

			$this->dbConnection();
			$pdo = $this->pdo;

			$update_query = "UPDATE `Batch_Report` SET status = 1 WHERE BatchId ='" . $id . "' ";
			$result = $pdo->prepare($update_query);
			$result->execute();

			if ($userType == 2 || $userType == 3 || $userType == 4) {
				$firm_code = explode(",", $code_name);
				$i = 0;
				foreach ($firm_code as $new_row) {

					$statement = "WITH CTE AS
					(
					SELECT ATTRNYCODE,
					(CASE WHEN RMSACCNTST = '4RA' THEN 'REASSIGN'
					      WHEN RMSACCNTST = '410' THEN 'NEW' ELSE 'REOPEN' END) AS cnt
					FROM aaca_backup.RMSPMASTER
					)
					SELECT ATTRNYCODE AS 'Firm', cnt AS 'ACCT TYPE(S)', count(cnt) AS '# Accts'
					FROM CTE
					GROUP BY ATTRNYCODE, cnt
					ORDER BY ATTRNYCODE, cnt";

					$query = $pdo->prepare($statement);
					$query->execute();
					$rows = $query->rowCount();

					if ($rows != 0) {

						$pdf = new TCPDF('P', 'mm', 'A4');

						//remove default header and footer
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);

						//add page
						$pdf->AddPage();
						//make page
						$html = "<table>
                          <tr>
                             <th>Firm</th>
                             <th>ACCT TYPE(S)</th>
                             <th># Accts</th>
                          </tr>
			";

						$data = $query->fetchAll();

						foreach ($data as $row) {

							$html .= "
                         <tr>
                            <td>" . $row['Firm'] . "</td>
                            <td>" . $row['ACCT TYPE(S)'] . "</td>
                            <td>" . $row['# Accts'] . "</td>
                          </tr>
               	         ";
						}

						$html .= "</table>
                          <style>
                           table { border-collpase:collpase;
                           }
                           th,td { border:1px solid #888
                           }
                           table tr th {
                           	    background-color:#888;
                           	    color:#fff;
                           	    font-weight:bold;
                           }
                          </style>

                ";


						$paths = explode(",", $path);

						if (!is_dir('/var/www/html/bi/dist/' . $paths[$i])) {
							mkdir('/var/www/html/bi/dist/' . $paths[$i], 0777, true);
							chmod('/var/www/html/bi/dist/' . $paths[$i], 0777);
						}

						$current_date = round(microtime(true) * 1000);

						$reportName = str_replace(' ', '_', "Open inventory reports");

						$filename = $reportName . "(" . $current_date . ").pdf";


						$filelocation = '/var/www/html/bi/dist/' . $paths[$i];

						$fileNL = $filelocation . "/" . $filename;

						//WriteHTMLCell
						$pdf->WriteHTMLCell(102, 0, 9, '', $html, 0);

						$pdf->Output($fileNL, 'F');
					}
					$i++;
				}
			}


			//          if($userType == 3)
			//          {
			//          $firm_code = explode(",",$code_name);
			//          $j = 0;
			//          foreach($firm_code as $new_row){

			//      	$statement = "WITH CTE AS
			// 		(
			// 		SELECT ATTRNYCODE,
			// 		(CASE WHEN RMSACCNTST = '4RA' THEN 'REASSIGN'
			// 		      WHEN RMSACCNTST = '410' THEN 'NEW' ELSE 'REOPEN' END) AS cnt
			// 		FROM aaca_backup.RMSPMASTER
			// 		)
			// 		SELECT ATTRNYCODE AS 'Firm', cnt AS 'ACCT TYPE(S)', count(cnt) AS '# Accts'
			// 		FROM CTE
			// 		GROUP BY ATTRNYCODE, cnt
			// 		ORDER BY ATTRNYCODE, cnt";

			// $query = $pdo->prepare($statement);
			// $query->execute();
			// $rows = $query->rowCount();  

			// if(count($rows) != 0)
			// {

			// $pdf = new TCPDF('P','mm','A4');

			// //remove default header and footer
			// $pdf->setPrintHeader(false);
			// $pdf->setPrintFooter(false);

			// //add page
			// $pdf->AddPage();
			// 	//make page
			// $html = "<table>
			//                        <tr>
			//                           <th>Firm</th>
			//                           <th>ACCT TYPE(S)</th>
			//                           <th># Accts</th>
			//                        </tr>
			// ";

			//             $data = $query->fetchAll(); 

			//             foreach ($data as $row) { 

			//             	$html .= "
			//                       <tr>
			//                          <td>".$row['Firm']."</td>
			//                          <td>".$row['ACCT TYPE(S)']."</td>
			//                          <td>".$row['# Accts']."</td>
			//                        </tr>
			//             	         "; 


			//             }

			//              $html .= "</table>
			//                        <style>
			//                         table { border-collpase:collpase;
			//                         }
			//                         th,td { border:1px solid #888
			//                         }
			//                         table tr th {
			//                         	    background-color:#888;
			//                         	    color:#fff;
			//                         	    font-weight:bold;
			//                         }
			//                        </style>

			//              ";


			//           $paths = explode(",",$path);

			// if (!is_dir('/var/www/html/bi/dist/'.$paths[$j])) {
			//             mkdir('/var/www/html/bi/dist/'.$paths[$j], 0777, true);
			//             chmod('/var/www/html/bi/dist/'.$paths[$j], 0777);
			//             }

			//          $current_date = round(microtime(true) * 1000);

			//          $reportName = str_replace(' ', '_', "Open inventory reports");

			//    $filename = $reportName."(".$current_date.").pdf";


			// $filelocation = '/var/www/html/bi/dist/'.$paths[$j]; 

			// $fileNL = $filelocation."/".$filename; 

			// //WriteHTMLCell
			//          $pdf->WriteHTMLCell(102,0,9,'',$html,0);

			// $pdf->Output($fileNL, 'F');



			//          }
			//          $j++;
			//          }
			//          }




			$update_query2 = "UPDATE `Batch_Report` SET status = 2 WHERE BatchId ='" . $id . "' ";
			$result2 = $pdo->prepare($update_query2);
			$result2->execute();






			$result->status = 1;
			echo $result->message = "PDF Successfully Created";
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();

			// $error_log_query = $pdo->prepare("INSERT INTO `SCHEDULER_ERROR_LOGS` (error_name,error_line_no) VALUES ('".$e->getMessage()."','".$e->getLine()."') ");
			//          $error_log_query->execute();

		}
	}

	public function fetchMonths($current_date)
	{

		$month = date('M', strtotime($current_date));

		if ($month == 'Jan') {
			$month = " AND FIND_IN_SET('Jan',months)";
		} else if ($month == 'Feb') {
			$month = " AND FIND_IN_SET('Feb',months)";
		} else if ($month == 'Mar') {
			$month = " AND FIND_IN_SET('Mar',months)";
		} else if ($month == 'Apr') {
			$month = " AND FIND_IN_SET('Apr',months)";
		} else if ($month == 'May') {
			$month = " AND FIND_IN_SET('May',months)";
		} else if ($month == 'Jun') {
			$month = " AND FIND_IN_SET('Jun',months)";
		} else if ($month == 'Jul') {
			$month = " AND FIND_IN_SET('Jul',months)";
		} else if ($month == 'Aug') {
			$month = " AND FIND_IN_SET('Aug',months)";
		} else if ($month == 'Sep') {
			$month = " AND FIND_IN_SET('Sep',months)";
		} else if ($month == 'Oct') {
			$month = " AND FIND_IN_SET('Oct',months)";
		} else if ($month == 'Nov') {
			$month = " AND FIND_IN_SET('Nov',months)";
		} else if ($month == 'Dec') {
			$month = " AND FIND_IN_SET('Dec',months)";
		}

		return $month;
	}

	public function fetchDayNew($total_days, $id, $reportName)
	{
		$day = date('d');

		$exp = explode(",", $total_days);

		// Last day(int) of the current month
		$lastday = date('t', strtotime(date('Y-m-d')));


		if ($day == $lastday) {
			if (in_array("32", $exp)) {
				$this->insertReportData($id, $reportName);
			} else {
				if (in_array($day, $exp)) {
					$this->insertReportData($id, $reportName);
				}
			}
		} else {
			if (in_array($day, $exp)) {
				$this->insertReportData($id, $reportName);
			}
		}
	}

	public function fetchDay($total_days, $id, $path, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		$day = date('d');

		$exp = explode(",", $total_days);

		// Last day(int) of the current month
		$lastday = date('t', strtotime(date('Y-m-d')));


		if ($day == $lastday) {
			if (in_array("32", $exp)) {
				$this->upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			} else {
				if (in_array($day, $exp)) {
					$this->upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
				}
			}
		} else {
			if (in_array($day, $exp)) {
				$this->upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
			}
		}
	}

	public function fetchBusinessDay($total_days, $id, $path, $reportName, $custom_start_date, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId)
	{
		try {


			$result = new stdClass();
			$this->dbConnection();
			$pdo = $this->pdo;
			$days = explode(",", $total_days);

			foreach ($days as $row) {
				if ($row == 32) {
					$holidays_list = "SELECT * FROM HOLIDAYS_LIST";
					$query = $pdo->prepare($holidays_list);
					$query->execute();

					$rows = $query->fetchAll();
					$query->closeCursor();

					$lastdateofthemonth = date("Y-m-t");

					$lastworkingday = date('l', strtotime($lastdateofthemonth));

					if ($lastworkingday == "Saturday") {
						$newdate = strtotime('-1 day', strtotime($lastdateofthemonth));
						$lastworkingday = date('Y-m-j', $newdate);

						foreach ($rows as $row) {
							if ($lastworkingday == $row['DATE']) {
								$newdate = strtotime('-1 day', strtotime($lastworkingday));
								$lastworkingday = date('Y-m-j', $newdate);
							}
						}
					} elseif ($lastworkingday == "Sunday") {
						$newdate = strtotime('-2 day', strtotime($lastdateofthemonth));
						$lastworkingday = date('Y-m-j', $newdate);

						foreach ($rows as $row) {
							if ($lastworkingday == $row['DATE']) {
								$newdate = strtotime('-1 day', strtotime($lastworkingday));
								$lastworkingday = date('Y-m-j', $newdate);
							}
						}
					} else {
						foreach ($rows as $row) {
							if ($lastdateofthemonth == $row['DATE']) {
								$newdate = strtotime('-1 day', strtotime($lastdateofthemonth));
								$lastworkingday = date('Y-m-j', $newdate);
							}
						}

						$lastworkingday2 = date('l', strtotime($lastworkingday));
						if ($lastworkingday2 == "Saturday") {
							$newdate = strtotime('-1 day', strtotime($lastworkingday));
							$lastworkingday = date('Y-m-j', $newdate);
						} elseif ($lastworkingday2 == "Sunday") {
							$newdate = strtotime('-2 day', strtotime($lastworkingday));
							$lastworkingday = date('Y-m-j', $newdate);
						}
					}

					if (date('Y-m-d') == $lastworkingday) {
						$this->upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
					}
				} else {

					$statement = "SELECT BUSINESSDATES('" . $custom_start_date . "',$row) as business_date";
					$query = $pdo->prepare($statement);
					$query->execute();

					$rows = $query->fetchAll();
					$query->closeCursor();

					if ($rows[0]['business_date'] == date('Y-m-d')) {

						$this->upload_monthly_report($path, $id, $reportName, $code_name, $userType, $userReportName, $outputName, $reportDescription, $mailNotification, $sftpId);
					}
				}
			}
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";

			echo 'Line no.',  $e->getLine();
		}
	}

	public function update_report_status()
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$statement = "SELECT BatchId FROM Batch_Report WHERE DATE(NOW()) BETWEEN StartDate AND EndDate AND IsDelete = 0 ";
		$query = $pdo->prepare($statement);
		$query->execute();

		$rows = $query->fetchAll();
		$query->closeCursor();


		if (count($rows) != 0) {

			foreach ($rows as $row) {

				$id = $row['BatchId'];

				$this->upload_data($id);
			}
		}

		$statement2 = "SELECT BatchId FROM Batch_Report WHERE EndDate = '' AND IsDelete = 0";
		$query2 = $pdo->prepare($statement2);
		$query2->execute();
		$rows2 = $query2->fetchAll();
		$query2->closeCursor();

		if (count($rows2) != 0) {

			foreach ($rows2 as $row) {

				$id = $row['BatchId'];

				$this->upload_data($id);
			}
		}
	}

	public function upload_data($id)
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$statement = "UPDATE `Batch_Report` SET status = 0 WHERE BatchId ='" . $id . "' ";
		$query = $pdo->prepare($statement);
		$query->execute();
		$query->closeCursor();
	}


	public function new_SMTP($userReportName, $outputName, $reportDescription, $folderName, $email)
	{
		try {
			//die(var_dump($row[0]['body']));
			$result = new stdClass();

			$mail = new PHPMailer;
			$mail->isSMTP();
			// $mail->Host = 'smtp.office365.com'; 
			$mail->Host = '172.16.13.208';
			// $mail->SMTPAuth = true;
			//    $mail->Username = 'pwadmin@aacanet.org';                 
			//    $mail->Password = 'Aaca1s@1';                           
			//    $mail->SMTPSecure = 'tls';                           
			$mail->Port = 25;
			$mail->From = 'pwadmin@aacanet.org';
			$mail->FromName = 'Pipeway 2.0';
			$mail->addAddress($email);
			$mail->isHTML(true);
			$mail->Subject = $userReportName;

			if ($folderName == 'MY DOWNLOADS') {
				$mail->Body = "<div style='margin-bottom:10px'>A new report called " . $outputName . " has been placed in your " . $folderName . " folder. The report " . $reportDescription . ". Please proceed to Pipeway to retrieve this report.<br> Thank you.</div>";
			} else {

				$mail->Body = "<div style='margin-bottom:10px'>A new report called " . $outputName . " has been placed in your my downloads under " . $folderName . " folder. The report " . $reportDescription . ". Please proceed to Pipeway to retrieve this report.<br> Thank you.</div>";
			}


			if (!$mail->send()) {
				$result->status = 0;
				echo "Email not sent. ", $mail->ErrorInfo, PHP_EOL;
			} else {
				$result->status = 1;
			}

			//$result->status = 1;
			return $result;
		} catch (Exception $e) {
		}
	}

	public function getFolderName($file)
	{
		if ($file == 'UD') {
			$result = 'ACCOUNT UPDATES';
		} else if ($file == 'AC') {
			$result = 'ACCOUNTING';
		} else if ($file == 'AD') {
			$result = 'ADMINISTRATION';
		} else if ($file == 'AF') {
			$result = 'AFFIDAVITS';
		} else if ($file == 'DP') {
			$result = 'BALANCE/ADJUSTMENT NOTIFICATION';
		} else if ($file == 'RM') {
			$result = 'CLIENT REMITTANCE';
		} else if ($file == 'CS') {
			$result = 'CLIENT SKIP RESULTS';
		} else if ($file == 'CO') {
			$result = 'COMPANY';
		} else if ($file == 'CP') {
			$result = 'COMPLIANCE';
		} else if ($file == 'CM') {
			$result = 'CONTRACT MASTER';
		} else if ($file == 'RD') {
			$result = 'DENIED RECALLS';
		} else if ($file == 'FT') {
			$result = 'FINANCIAL TRANSACTION';
		} else if ($file == 'KH') {
			$result = 'HOLD LIST REVIEW';
		} else if ($file == 'JC') {
			$result = 'JUDGMENT INFORMATION FILE';
		} else if ($file == 'JD') {
			$result = 'JUDGMENT REPORT';
		} else if ($file == 'KP') {
			$result = 'KEEPER LIST REQUEST';
		} else if ($file == 'MD') {
			$result = 'MEDIA';
		} else if ($file == 'IT') {
			$result = 'MIS DEPT';
		} else if ($file == 'PH') {
			$result = 'PHONE REPORT';
		} else if ($file == 'PA') {
			$result = 'PLACEMENT ALLOCATION';
		} else if ($file == 'PL') {
			$result = 'PLACEMENTS';
		} else if ($file == 'RA') {
			$result = 'REASSIGNED PLACEMENTS';
		} else if ($file == 'RC') {
			$result = 'RECALL';
		} else if ($file == 'RJ') {
			$result = 'REJECT';
		} else if ($file == 'RO') {
			$result = 'REOPENED PLACEMENTS';
		} else if ($file == 'WS') {
			$result = 'WEB SERVICES';
		} else if ($file == 'DP') {
			$result = 'DIRECT PAYMENT';
		} else if ($file == 'TR') {
			$result = 'TEST REPORT';
		} else {
			$result = 'MY DOWNLOADS';
		}

		return $result;
	}

	public function getEmailId($code, $userType, $folderName, $path)
	{


		$this->dbConnection();
		$pdo = $this->pdo;
		if ($userType == 1) {
			if ($folderName == 'MY DOWNLOADS') {
				$split = explode("/", $path);
				$newFileName = $split[count($split) - 1];


				$fetchQuery = $pdo->prepare("SELECT DISTINCT RGEMAIL FROM WSREGUSR WHERE RGUSER = '" . $newFileName . "'");
				$fetchQuery->execute();
				$rows_data = $fetchQuery->fetchAll();
				$fetchQuery->closeCursor();
				if (count($rows_data)) {
					foreach ($rows_data as $row)
						$result[] = $row['RGEMAIL'];
				} else {
					$result[] = $newFileName;
				}
			} else {
				$statement = "SELECT DISTINCT email from tbl_login WHERE FIND_IN_SET('" . $folderName . "',UserGroup) AND userType = 1 AND company_status != 4 AND bit_deleted_flag = 0";

				$query = $pdo->prepare($statement);
				$query->execute();
				$rows = $query->fetchAll();
				$query->closeCursor();

				if (count($rows)) {
					foreach ($rows as $row)
						$result[] = $row['email'];
				}
			}
		}
		if ($userType == 2) {

			if ($folderName == 'MY DOWNLOADS') {
				$split = explode("/", $path);
				$newFileName = $split[count($split) - 1];


				$fetchQuery = $pdo->prepare("SELECT DISTINCT RGEMAIL FROM WSREGUSR WHERE RGUSER = '" . $newFileName . "'");
				$fetchQuery->execute();
				$rows_data = $fetchQuery->fetchAll();
				$fetchQuery->closeCursor();
				if (count($rows_data)) {
					foreach ($rows_data as $row)
						$result[] = $row['RGEMAIL'];
				} else {
					$result[] = $newFileName;
				}
			} else {
				$statement = "SELECT DISTINCT email from tbl_login  WHERE ((FIND_IN_SET('" . $code . "',firmCode))>0 OR firmCode='ALL') AND FIND_IN_SET('" . $folderName . "',UserGroup) AND userType = 2 AND company_status != 4 AND bit_deleted_flag = 0 ";

				$query = $pdo->prepare($statement);
				$query->execute();
				$rows = $query->fetchAll();
				$query->closeCursor();

				if (count($rows)) {
					foreach ($rows as $row)
						$result[] = $row['email'];
				}
			}
		} else if ($userType == 3) {
			if ($folderName == 'MY DOWNLOADS') {
				$split = explode("/", $path);
				$newFileName = $split[count($split) - 1];


				$fetchQuery = $pdo->prepare("SELECT DISTINCT RGEMAIL FROM WSREGUSR WHERE RGUSER = '" . $newFileName . "'");
				$fetchQuery->execute();
				$rows_data = $fetchQuery->fetchAll();
				$fetchQuery->closeCursor();
				if (count($rows_data)) {
					foreach ($rows_data as $row)
						$result[] = $row['RGEMAIL'];
				} else {
					$result[] = $newFileName;
				}
			} else {
				$statement = "SELECT DISTINCT email from tbl_login  WHERE ((FIND_IN_SET('" . $code . "',clientCode))>0 OR clientCode='ALL') AND FIND_IN_SET('" . $folderName . "',UserGroup) AND userType = 3 AND company_status != 4 AND bit_deleted_flag = 0 ";

				$query = $pdo->prepare($statement);
				$query->execute();
				$rows = $query->fetchAll();
				$query->closeCursor();

				if (count($rows)) {
					foreach ($rows as $row)
						$result[] = $row['email'];
				}
			}
		} else if ($userType == 4) {
			if ($folderName == 'MY DOWNLOADS') {
				$split = explode("/", $path);
				$newFileName = $split[count($split) - 1];


				$fetchQuery = $pdo->prepare("SELECT DISTINCT RGEMAIL FROM WSREGUSR WHERE RGUSER = '" . $newFileName . "'");
				$fetchQuery->execute();
				$rows_data = $fetchQuery->fetchAll();
				$fetchQuery->closeCursor();
				if (count($rows_data)) {
					foreach ($rows_data as $row)
						$result[] = $row['RGEMAIL'];
				} else {
					$result[] = $newFileName;
				}
			} else {
				$statement = "SELECT DISTINCT email from tbl_login  WHERE ((FIND_IN_SET('" . $code . "',firmCode))>0 OR firmCode='ALL') AND FIND_IN_SET('" . $folderName . "',UserGroup) AND userType = 4 AND company_status != 4 AND bit_deleted_flag = 0 ";

				$query = $pdo->prepare($statement);
				$query->execute();
				$rows = $query->fetchAll();
				$query->closeCursor();

				if (count($rows)) {
					foreach ($rows as $row)
						$result[] = $row['email'];
				}
			}
		}




		return $result;
	}


	public function sftpCredentials($sftpId, $paths, $filename, $code, $status)
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$statement = "SELECT * FROM SCHEDULER_SFTP WHERE id IN ($sftpId)";
		$query = $pdo->prepare($statement);
		$query->execute();
		$rows = $query->fetchAll();
		$query->closeCursor();

		if (count($rows) != 0) {

			foreach ($rows as $row) {

				if ($row['code'] == $code) {

					$conn = ssh2_connect($row['host_name'], 22);
					$sftp = ssh2_auth_password($conn, $row['username'], base64_decode($row['password']));

					if (!$sftp) {

						$msg = "Login failed for " . $row['host_name'] . " server, please check the credentials";
						$status = 0;
						$status_msg = "login failed";

						copy('/var/www/html/bi/dist/' . $paths . '/' . $filename, '/var/www/html/bi/dist/MAKO/downloadfile/AACA/RF/' . $filename);
					} else {
						if (ssh2_scp_send($conn, "/var/www/html/bi/dist/" . $paths . "/" . $filename, $row['path'] . $filename, 0644)) {
							$msg = "Report generated successfully for " . $row['host_name'] . " server";
							$status = 1;
							$status_msg = "generated";
						} else {

							$msg = "Uploading error for " . $row['host_name'] . " server";
							$status = 0;
							$status_msg = "uploading error";

							copy('/var/www/html/bi/dist/' . $paths . '/' . $filename, '/var/www/html/bi/dist/MAKO/downloadfile/AACA/RF/' . $filename);
						}
					}

					$result = array('msg' => $msg, 'status' => $status, 'status_msg' => $status_msg);
				} else {
					$msg = "Report generated successfully";
					$status = 1;
					$status_msg = "generated";

					$result = array('msg' => $msg, 'status' => $status, 'status_msg' => $status_msg);
				}
			}
		}

		return $result;
	}


	public function scheduler_logs($status, $reportName, $report_start_time, $msg, $new_row, $sftpStatus)
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$current_date = date("Y-m-d");
		$report_end_time = date("H:i:s");
		// if($status == 0)
		// {
		// 	$msg = 'mysql error';
		// }

		$insert_logs = "INSERT INTO `SCHEDULER_LOGS` (report_name,start_time,end_time,status,message,code,sftp,createdAt) VALUES ('" . $reportName . "','" . $report_start_time . "','" . $report_end_time . "','" . $status . "','" . $msg . "','" . $new_row . "','" . $sftpStatus . "','" . $current_date . "') ";

		$log_query = $pdo->prepare($insert_logs);
		$log_query->execute();

		if ($status == 0) {
			if ($new_row == 'ALL') {
				$new_row = 'AACA';
			}

			$message = '<div>Hi,</div><br>';
			$message .= 'The report "' . $reportName . '" has been failed for ' . $new_row . ' recepients.<br>';
			$message .= 'Error message : "' . $msg . '" <br>';
			$message .= 'For further information please go through <b>/home/biuser/scheduler_cron.txt</b> <br>';
			$message .= "Thank you.";

			$subject = 'Scheduler Report Failure';

			$contacts = array("deepak.verma@goolean.tech", "santosh.nerake@knovaone.com");

			foreach ($contacts as $contact) {

				$this->Send_email($subject, $message, $contact);
			}
		}
	}

	public function Send_email($subject, $body, $email)
	{
		try {
			//die(var_dump($row[0]['body']));
			$result = new stdClass();

			$mail = new PHPMailer;
			$mail->isSMTP();
			// $mail->Host = 'smtp.office365.com';
			// adding mail after changeing post fix  // nilesh 18-11-25
			$mail->Host = '172.16.13.208';
			// $mail->SMTPAuth = true;
			// $mail->Username = 'pwadmin@aacanet.org';
			// $mail->Password = 'Aaca1s@1';
			// $mail->SMTPSecure = 'tls';
			$mail->Port = 25;
			$mail->From='pwadmin@aacanet.org';
			// $mail->From = 'donotreply@aacanet.org';
			$mail->FromName = 'Pipeway 2.0';
			$mail->addAddress($email);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $body;


			if (!$mail->send()) {
				$result->status = 0;
				echo "Email not sent. ", $mail->ErrorInfo, PHP_EOL;
			} else {
				$result->status = 1;
			}

			//$result->status = 1;
			return $result;
		} catch (Exception $e) {
		}
	}

	public function dailyReportMail()
	{
		$this->dbConnection();
		$pdo = $this->pdo;
		$current_date = date("Y-m-d");

		$statement = "SELECT * FROM SCHEDULER_LOGS WHERE createdAt = '" . $current_date . "' AND is_sent = 0";
		$query = $pdo->prepare($statement);
		$query->execute();
		$rows = $query->fetchAll();
		$query->closeCursor();




		if (count($rows) != 0) {

			$message = '<div>Hi,</div><br>';
			$message .= 'Please find the daily scheduled report status.<br><br>';
			$message .= '<table rules="all" style="border-color: #666; table-layout: fixed; width: 450px;" cellpadding="10">';
			$message .= "<tr style='background: #eee;'><th>Report Name:</th><th style='width: 78px;'>Recipients:</th><th>Start Time:</th><th>End Time:</th><th>Report Status:</th></tr>";


			foreach ($rows as $value) {



				if ($value['status'] == 1) {
					$status = "Success";
				} else {
					$status = "Failed";
				}

				if ($value['code'] == "ALL") {
					$code = "AACA";
				} else {
					$code = $value['code'];
				}


				$message .= "<tr style='background: #eee;'><td>" . $value['report_name'] . "</td><td>" . $code . "</td><td>" . $value['start_time'] . "</td><td>" . $value['end_time'] . "</td><td>" . $status . "</td></tr>";
			}

			$message .= "</table>";

			$message .= "Thank you.";
			$date = date("m/d/Y");
			$subject = 'Scheduled Report Status ' . $date . '';

			$contacts = array("bandana.kumari@goolean.tech", "nilesh.karanjkar@goolean.tech", "nitin.kumar@goolean.tech");

			foreach ($contacts as $contact) {

				$this->Send_email($subject, $message, $contact);
			}

			$statement2 = "UPDATE SCHEDULER_LOGS SET is_sent = 1 WHERE createdAt = '" . $current_date . "' ";
			$query2 = $pdo->prepare($statement2);
			$query2->execute();
		}
	}
}



$p = new DailyReport();
$p->start();
