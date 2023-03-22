<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\NumericDef;

class OreDictQueryEntryApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams(): array {
        return array(
            'ids' => array(
                ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_ALLOW_DUPLICATES => false,
				NumericDef::PARAM_MIN => 1,
				ParamValidator::PARAM_REQUIRED => true,
            ),
        );
    }

    public function getExamples(): array {
        return array(
            'api.php?action=query&prop=oredictentry&odids=1|2|3|4',
        );
    }

    public function execute() {
        $ids = $this->getParameter('ids');
        $dbr = $this->getDB();
        $ret = array();

        foreach ($ids as $id) {
            $results = $dbr->select('ext_oredict_items', '*', array('entry_id' => $id));
            if ($results->numRows() > 0) {
                $row = $results->current();
                $ret[$id] = OreDict::getArrayFromRow( $row) ;
            } else {
                $ret[$id] = null;
            }
        }

        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}
