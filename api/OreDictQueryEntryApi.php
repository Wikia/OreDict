<?php

class OreDictQueryEntryApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'od');
    }

    public function getAllowedParams() {
        return array(
            'ids' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_ISMULTI => true,
                ApiBase::PARAM_ALLOW_DUPLICATES => false,
                ApiBase::PARAM_MIN => 1,
                ApiBase::PARAM_REQUIRED => true,
            ),
        );
    }

    public function getExamples() {
        return array(
            'api.php?action=query&prop=oredictentry&odids=1|2|3|4',
        );
    }

    public function execute() {
        $ids = $this->getParameter('ids');
        $dbr = wfGetDB(DB_REPLICA);
        $ret = array();

        foreach ($ids as $id) {
            $results = $dbr->select('ext_oredict_items', '*', array('entry_id' => $id));
            if ($results->numRows() > 0) {
                $row = $results->current();
                $ret[$id] = OreDict::getArrayFromRow($row);
            } else {
                $ret[$id] = null;
            }
        }

        $this->getResult()->addValue('query', 'oredictentries', $ret);
    }
}
