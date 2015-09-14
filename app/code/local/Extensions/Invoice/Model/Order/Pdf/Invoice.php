<?php
/**
 * Extensions PDF rewrite for custom attribute
 * * Attribute "extensions" has to be set manually
 * Original: Sales Order Invoice PDF model
 *
 * @category   Extensions
 * @package    Extensions_Invoice
 * @author     Jawad Nisar - Extensions <jawwad.nissar@gmail.com>
 */
class Extensions_Invoice_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{
	protected function insertImage($image, $x1, $y1, $x2, $y2, $width, $height, &$page)
	{
		if (!is_null($image)) {
			try{
				$width = (int) $width;
				$height = (int) $height;
//echo Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

  $imageurl = Mage::helper('catalog/image')->init($image, 'small_image');
//echo $imageurl .  "here2";
//				
//echo "<br />";
//echo "<pre>";
//print_r($image->getData());
//echo "</pre>";
//
//exit;

				//Get product image and resize it
//				$imagePath = Mage::helper('catalog/image')->init($image, 'image')
//					->keepAspectRatio(true)
//					->keepFrame(false)
//					->resize($width, $height)
//					->__toString();
 				$imageLocation = substr($imageurl,strlen(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)));
				//echo $imageLocation."here3";
				$image = Zend_Pdf_Image::imageWithPath($imageLocation);
				//echo $image . "here4";



				//Draw image to PDF
				$page->drawImage($image, $x1, $y1, $x2, $y2);
			}
			catch (Exception $e) {
				return false;
			}
		}
	}

	public function getPdf($invoices = array())
    {
		$width = 1000;
		$height = 1000;
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');
 
        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
 
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
            }
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;
 
            $order = $invoice->getOrder();
 
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
 
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
 
            /* Add head */
            $this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId()));
 
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId(), 450, 820, 'UTF-8');
 
 
            /* Add table head */
			$page->setFillColor(new Zend_Pdf_Color_Rgb(0,0,0)); // background color
			$page->setLineWidth(0.5);
			$page->drawRectangle(25,  $this->y, 100, $this->y-25);
			$page->drawRectangle(100, $this->y, 350, $this->y-25);
			$page->drawRectangle(350, $this->y, 450, $this->y-25);
			$page->drawRectangle(450, $this->y, 500, $this->y-25);
			$page->drawRectangle(500, $this->y, 570, $this->y-25);
	
			$this->y -= 15;
			$this->_setFontBold($page, 12);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(1)); // heading color
			$page->drawText(Mage::helper('sales')->__('Image'), 35, $this->y, 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Product'), 110, $this->y , 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Price'), 360, $this->y , 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Qty'), 460, $this->y , 'UTF-8');
			$page->drawText(Mage::helper('sales')->__('Sub Total'), 510, $this->y , 'UTF-8');
 
            $this->y -=25;
 
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
 
            /* Add body */
            foreach ($invoice->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                if ($this-> y < 15) {
                    $page = $this->newPage(array('table_header' => true));
                }
 
				/* Draw product image */
				$productId = $item->getOrderItem()->getProductId();
				$productSku = $item->getOrderItem()->getSku();
				$image = Mage::getModel('catalog/product')->load($productId);
				$this->insertImage($image, 30, (int)($this->y-50), 90, (int)($this->y+5), $width, $height, $page);



                /* Draw item */
                $page = $this->_drawItem($item, $page, $order);
				$this->_setFontRegular($page, 8);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
				$this->y = $this->y - 60;
				$page->drawLine(25, $this->y+15, 570, $this->y+15);
				

				/* Start Barcode */
				$dir_invoicebarcode = "invoicebarcode";
				$barCodeNo = $productSku; //For Order Number add this: $order->getIncrementId()
				
				header('Content-Type: image/png');
				$barcodeOptions = array('text' => $barCodeNo, 'barHeight'=> 1, 'factor'=>1,'drawText' => false);
				$rendererOptions = array();
				$imageResource = Zend_Barcode::draw(
				   'code128', 'image', $barcodeOptions, $rendererOptions
				);
				$upload_path = str_replace("\/","/",Mage::getBaseDir('media').DS.$dir_invoicebarcode.DS.$barCodeNo."_barcode.png");
				chmod($upload_path,0777);
				imagepng($imageResource,$upload_path, 0, NULL);
				imagedestroy($imageResource);
				
				$barcode_image = $upload_path;
				$barcode_y = $this-> y + 43;
				if (is_file($barcode_image)) {
				   $barcode_image = Zend_Pdf_Image::imageWithPath($barcode_image);
				   $page->drawImage($barcode_image, 100, $barcode_y-20, 350, $barcode_y);
				}
				/*End Barcode*/
            }
 
            /* Add totals */
            $page = $this->insertTotals($page, $invoice);
 
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }


 

		// footer
		$this->_setFontRegular($page, 8);
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		$page->drawLine(25, 10, 570, 10);
		$page->drawText(Mage::helper('sales')->__('Thank you, Creo'), 510, 60, 'UTF-8');
		$page->drawText(Mage::helper('sales')->__('Creo, +971 4 331 0717, 9:00am to 9:00pm, Rand Almaeeni, Grosvenor Business Tower, Office 1211 Dubai - United Arab Emirates'), 70, 17, 'UTF-8');
		$this->_setFontRegular($page, 8);
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		$page->drawLine(25, 30, 570, 30);
        $this->_afterGetPdf();

        return $pdf;
    }
 
	public function newPage(array $settings = array())
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
 
        if (!empty($settings['table_header'])) {
            $this->_setFontRegular($page);
            $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 570, $this->y-15);
            $this->y -=10;
 
            $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
			$page->drawText(Mage::helper('sales')->__('Image'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Product'), 100, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('SKU'), 325, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Qty'), 430, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');
 
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $this->y -=20;
        }
        return $page;
    }
	protected function _drawFooter(Zend_Pdf_Page $page)
	{
//		/* Add table foot */
//		$this->_setFontRegular($page, 8);
//		$this->y -= 280;
//		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
//		$page->drawLine(25, 20, 570, 20);
//		
//		$page->drawText(Mage::helper('sales')->__('Creo, +971 4 331 0717, 9:00am to 9:00pm, Rand Almaeeni, Grosvenor Business Tower, Office 1211 Dubai'), 35, $this->y-65, 'UTF-8');
//		$page->drawText(Mage::helper('sales')->__('United Arab Emirates'), 35, $this->y-105, 'UTF-8');		
//	  	$page->drawLine(25, $this->y-120, 570, $this->y-120);
  
    }
}