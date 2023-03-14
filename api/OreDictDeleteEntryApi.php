<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\NumericDef;

class OreDictDeleteEntryApi extends ApiBase {
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
            'token' => null,
        );
    }

    public function needsToken(): string {
        return 'csrf';
    }

    public function getTokenSalt(): string {
        return '';
    }

    public function mustBePosted(): bool {
        return true;
    }

    public function isWriteMode(): bool {
        return true;
    }

    public function getExamples(): array {
        return array(
            'api.php?action=deleteoredict&odids=1|2|3',
        );
    }

    public function execute() {
		if ( !$this->getUser()->isAllowed( 'editoredict' ) ) {
            $this->dieWithError('You do not have the permission to add OreDict entries', 'permissiondenied');
        }
        $entryIds = $this->getParameter('ids');
        $ret = array();

        foreach ($entryIds as $id) {
            if (OreDict::checkExistsByID($id)) {
                $result = OreDict::deleteEntry($id, $this->getUser());
                $ret[$id] = $result;
            } else {
                $ret[$id] = false;
            }
        }

        $this->getResult()->addValue('edit', 'deleteoredict', $ret);
    }
}
