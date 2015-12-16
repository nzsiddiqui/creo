<?php
class MST_Pdp_Block_Adminhtml_Shape_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shapeGrid');
        $this->setSaveParametersInSession(true);
    }
    protected function _prepareLayout()
	{
	    $this->unsetChild('reset_filter_button');
	    $this->unsetChild('search_button');
	}
}