<?php

namespace Hschottm\SurveyBundle;

use Contao\DataContainer;
use Hschottm\ExcelXLSBundle\xlsexport;

/**
 * Class SurveyPINTAN
 *
 * Provide methods to handle import and export of member data.
 * @copyright  Helmut Schottmüller 2009-2010
 * @author     Helmut Schottmüller <contao@aurealis.de>
 * @package    Controller
 */
class SurveyPINTAN extends \Backend
{
	protected $blnSave = true;

	public function exportTAN(DataContainer $dc)
	{
		if (\Input::get('key') != 'exporttan')
		{
			return '';
		}

    if (\Input::get('table') === 'tl_survey_pin_tan')
    {
      $this->redirect(\Backend::addToUrl("table=tl_survey", true, ['table']));
      return;
    }

		$this->loadLanguageFile("tl_survey_pin_tan");
		$this->Template = new \BackendTemplate('be_survey_export_tan');

		$this->Template->surveyPage = $this->getSurveyPageWidget();

		$this->Template->hrefBack = \Backend::addToUrl("table=tl_survey_pin_tan", true, ['table','key']);
		$this->Template->goBack = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->headline = $GLOBALS['TL_LANG']['tl_survey_pin_tan']['exporttan'];
		$this->Template->request = ampersand(str_replace('&id=', '&pid=', \Environment::get('request')));
		$this->Template->submit = \StringUtil::specialchars($GLOBALS['TL_LANG']['tl_survey_pin_tan']['export']);

		// Create import form
		if (\Input::post('FORM_SUBMIT') == 'tl_export_survey_pin_tan' && $this->blnSave)
		{
			$export = array();
			$surveyPage = $this->Template->surveyPage->value;
      $pageModel = \PageModel::findOneBy('id', $surveyPage);
      $pagedata = (null != $pageModel) ? $pageModel->row() : null;
			$domain = \Environment::get('base');

      $res = \Hschottm\SurveyBundle\SurveyPinTanModel::findBy('pid', \Input::get('pid'), array("order" => "tstamp DESC, id DESC"));
			foreach ($res as $objPINTAN)
			{
				$row = $objPINTAN->row();
				$line = array();
				$line['tan'] = $row['tan'];
				$line['tstamp'] = date($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);
				$line['used'] = $row['used'] ? 1 : 0;
				if (!is_null($pagedata))
				{
					$line['url'] = ampersand($domain . $this->generateFrontendUrl($pagedata, '/code/' . $row['tan']));
				}
				$export[] = $line;
			}
			if (count($export))
			{
				$xls = new xlsexport();
				$sheet = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['tans']);
				$xls->addworksheet($sheet);

				// Headers
				$intRowCounter = 0;
				$intColCounter = 0;

				$data = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['tan'][0]);
				$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'fontweight' => XLSFONT_BOLD, 'data' => $data));
				$intColCounter++;

				$data = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['tstamp'][0]);
				$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'fontweight' => XLSFONT_BOLD, 'color' => 'gray', 'data' => $data));
				$xls->setcolwidth($sheet, $intColCounter, max(strlen(date($GLOBALS['TL_CONFIG']['datimFormat'])) + 1, strlen($data) + 1) * 256);
				$intColCounter++;

				$data = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['used'][0]);
				$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'fontweight' => XLSFONT_BOLD, 'data' => $data));
				$intColCounter++;

				if (!is_null($pagedata))
				{
					$data = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['url']);
					$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'fontweight' => XLSFONT_BOLD, 'data' => $data));
					$xls->setcolwidth($sheet, $intColCounter, max(strlen($data) + 1, strlen($export[0]['url']) + 1) * 256);
					$intColCounter++;
				}

				$data = utf8_decode($GLOBALS['TL_LANG']['tl_survey_pin_tan']['sort']);
				$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'fontweight' => XLSFONT_BOLD, 'color' => 'gray', 'data' => $data));
				$xls->setcolwidth($sheet, $intColCounter, (strlen($data) + 1) * 256);

				// Data
				$intRowCounter = 1;
				foreach ($export as $line)
				{
					$intColCounter = 0;
					foreach ($line as $key => $data)
					{
						$cell = array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'data' => $data);
						if ($key == 'tstamp')
						{
							$cell['color'] = 'gray';
						}
						elseif ($key == 'used' && $data)
						{
							$cell['bgcolor'] = 'red';
						}
						$xls->setcell($cell);
						$intColCounter++;
					}
					$xls->setcell(array('sheetname' => $sheet, 'row' => $intRowCounter, 'col' => $intColCounter, 'data' => $intRowCounter, 'color' => 'gray', 'type' => CELL_FLOAT));
					$intRowCounter++;
				}
        $surveyModel = \Hschottm\SurveyBundle\SurveyModel::findOneBy('id', \Input::get('pid'));
				if (null != $surveyModel)
				{
					$xls->sendFile(\StringUtil::sanitizeFileName('TAN_' . htmlspecialchars_decode($surveyModel->title) . ".xls"));
				}
				else
				{
					$xls->sendFile('TAN.xls');
				}
				exit;
			}
      $this->redirect(\Backend::addToUrl("table=tl_survey_pin_tan", true, ['key','table']));
		}
		return $this->Template->parse();
	}

	public function createTAN(DataContainer $dc)
	{
		if (\Input::get('key') != 'createtan')
		{
			return '';
		}

		$this->loadLanguageFile("tl_survey_pin_tan");
		$this->Template = new \BackendTemplate('be_survey_create_tan');

		$this->Template->nrOfTAN = $this->getTANWidget();

		$this->Template->hrefBack = ampersand(str_replace('&key=createtan', '', \Environment::get('request')));
		$this->Template->goBack = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->headline = $GLOBALS['TL_LANG']['tl_survey_pin_tan']['createtan'];
		$this->Template->request = ampersand(\Environment::get('request'), ENCODE_AMPERSANDS);
		$this->Template->submit = \StringUtil::specialchars($GLOBALS['TL_LANG']['tl_survey_pin_tan']['create']);

		// Create import form
		if (\Input::post('FORM_SUBMIT') == 'tl_export_survey_pin_tan' && $this->blnSave)
		{
			$nrOfTAN = $this->Template->nrOfTAN->value;
      $this->import('\Hschottm\SurveyBundle\Survey', 'svy');
			for ($i = 0; $i < ceil($nrOfTAN); $i++)
			{
				$pintan = $this->svy->generatePIN_TAN();
        $this->insertPinTan(\Input::get('id'), $pintan["PIN"], $pintan["TAN"]);
			}
      $this->redirect(\Backend::addToUrl("", true, ['key']));
		}
		return $this->Template->parse();
	}

  protected function insertPinTan($pid, $pin, $tan)
  {
    $newParticipant         = new \Hschottm\SurveyBundle\SurveyPinTanModel();
    $newParticipant->tstamp = time();
    $newParticipant->pid    = $pid;
    $newParticipant->pin    = $pin;
    $newParticipant->tan    = $tan;
    $newParticipant->save();
  }

	/**
	 * Return the page tree as object
	 * @param mixed
	 * @return object
	 */
	protected function getSurveyPageWidget($value=null)
	{
		$widget = new \PageTree(\Widget::getAttributesFromDca($GLOBALS['TL_DCA']['tl_survey']['fields']['surveyPage'], 'surveyPage', $value, 'surveyPage', 'tl_survey'));
		if ($GLOBALS['TL_CONFIG']['showHelp'] && strlen($GLOBALS['TL_LANG']['tl_survey']['surveyPage'][1]))
		{
			$widget->help = $GLOBALS['TL_LANG']['tl_survey']['surveyPage'][1];
		}

		// Valiate input
		if (\Input::post('FORM_SUBMIT') == 'tl_export_survey_pin_tan')
		{
			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->blnSave = false;
			}
		}

		return $widget;
	}

	/**
	 * Return the TAN widget as object
	 * @param mixed
	 * @return object
	 */
	protected function getTANWidget($value=null)
	{
		$widget = new \TextField();

		$widget->id = 'nrOfTAN';
		$widget->name = 'nrOfTAN';
		$widget->mandatory = true;
		$widget->maxlength = 5;
		$widget->rgxp = 'digit';
		$widget->nospace = true;
		$widget->value = $value;
		$widget->required = true;

		$widget->label = $GLOBALS['TL_LANG']['tl_survey_pin_tan']['nrOfTAN'][0];

		if ($GLOBALS['TL_CONFIG']['showHelp'] && strlen($GLOBALS['TL_LANG']['tl_survey_pin_tan']['nrOfTAN'][1]))
		{
			$widget->help = $GLOBALS['TL_LANG']['tl_survey_pin_tan']['nrOfTAN'][1];
		}

		// Valiate input
		if (\Input::post('FORM_SUBMIT') == 'tl_export_survey_pin_tan')
		{
			$widget->validate();

			if ($widget->hasErrors())
			{
				$this->blnSave = false;
			}
		}

		return $widget;
	}
}
