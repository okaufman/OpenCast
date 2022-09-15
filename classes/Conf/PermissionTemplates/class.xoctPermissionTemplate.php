<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctPermissionTemplate
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplate extends ActiveRecord {

	const TABLE_NAME = 'xoct_perm_template';


	/**
	 * @return string
	 * @deprecated
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_fieldtype        integer
	 * @db_length           8
	 * @con_sequence        true
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $sort;
    /**
     * @var bool
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $is_default = 0;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title_de;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $title_en;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $info_de;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $info_en;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $role;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $read_access;
	/**
	 * @var Integer
	 *
	 * @db_has_field        true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $write_access;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_acl_actions;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_actions_download;
	/**
	 * @var String
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $additional_actions_annotate;

    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role;

    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_name;

    /**
     * @var Integer
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role_read_access;
    /**
     * @var Integer
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           1
     */
    protected $added_role_write_access;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_acl_actions;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_actions_download;
    /**
     * @var String
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $added_role_actions_annotate;


	public function create() {
		$this->setSort(self::count() + 1);
		parent::create();
	}


	/**
	 * @param array $acls
	 */
	public static function removeAllTemplatesFromAcls(array &$acls) {
		if (empty($acls)) {
			return;
		}

		/** @var xoctPermissionTemplate $perm_tpl */
		foreach (self::get() as $perm_tpl) {
			$perm_tpl->removeFromAcls($acls);
		}
	}


	/**
	 * @param array $acls
	 *
	 * @return xoctPermissionTemplate|bool
	 */
	public static function getTemplateForAcls(array $acls) {
		$acls_formatted = array();
		foreach ($acls as $acl) {
			if (!isset($acls_formatted[$acl->getRole()])) {
				$acls_formatted[$acl->getRole()] = array();
			}
			$acls_formatted[$acl->getRole()][$acl->getAction()] = $acl->isAllow();
		}

		/** @var xoctPermissionTemplate $perm_tpl */
		foreach (self::get() as $perm_tpl) {
			$acl = $acls_formatted[$perm_tpl->getRole()];
			if ($acl && (isset($acl[xoctAcl::READ]) == (bool)$perm_tpl->getRead()) && (isset($acl[xoctAcl::WRITE]) == (bool)$perm_tpl->getWrite())) {
				foreach (explode(',', $perm_tpl->getAdditionalAclActions()) as $action) {
					if (!$acl[trim($action)]) {
						continue 2;
					}
				}

				return $perm_tpl;
			}
		}

		return false;
	}


	/**
	 * @param array $acls
	 * @param       $with_download
	 * @param       $with_annotate
	 */
	public function addToAcls(array &$acls, $with_download, $with_annotate) {
		$this->removeFromAcls($acls);
		$acls = array_merge($acls, $this->getAcls($with_download, $with_annotate));
	}


	/**
	 * @param array $acls
	 */
	public function removeFromAcls(array &$acls) {
		/** @var xoctAcl $existing_acl */
		foreach ($acls as $key => $existing_acl) {
			if ($existing_acl->getRole() == $this->getRole() || $existing_acl->getRole() == $this->getAddedRoleName()) {
				unset($acls[$key]);
			}
		}
	}


	/**
	 * @param $with_download
	 * @param $with_annotate
	 *
	 * @return array
	 */
	public function getAcls($with_download, $with_annotate) {
		$acls = array();

		if ($this->getRead()) {
			$acls[] = $this->constructAclForAction(xoctAcl::READ);
		}

		if ($this->getWrite()) {
			$acls[] = $this->constructAclForAction(xoctAcl::WRITE);
		}

		foreach (array_filter(explode(',', $this->getAdditionalAclActions())) as $additional_action) {
			$acls[] = $this->constructAclForAction($additional_action);
		}

		if ($with_download && $this->getAdditionalActionsDownload()) {
			foreach (explode(',', $this->getAdditionalActionsDownload()) as $additional_action) {
				$acls[] = $this->constructAclForAction($additional_action);
			}
		}

		if ($with_annotate && $this->getAdditionalActionsAnnotate()) {
			foreach (explode(',', $this->getAdditionalActionsAnnotate()) as $additional_action) {
				$acls[] = $this->constructAclForAction($additional_action);
			}
		}

        if($this->getAddedRole()) {
            $role_name = $this->getAddedRoleName();
            if ($this->getAddedRoleRead()) {
                $acls[] = $this->constructAclActionForRole(xoctAcl::READ, $role_name);
            }

            if ($this->getAddedRoleWrite()) {
                $acls[] = $this->constructAclActionForRole(xoctAcl::WRITE, $role_name);
            }

            foreach (array_filter(explode(',', $this->getAddedRoleAclActions())) as $additional_action) {
                $acls[] = $this->constructAclActionForRole($additional_action, $role_name);
            }

            if ($with_download && $this->getAddedRoleActionsDownload()) {
                foreach (explode(',', $this->getAddedRoleActionsDownload()) as $additional_action) {
                    $acls[] = $this->constructAclActionForRole($additional_action, $role_name);
                }
            }

            if ($with_annotate && $this->getAddedRoleActionsAnnotate()) {
                foreach (explode(',', $this->getAddedRoleActionsAnnotate()) as $additional_action) {
                    $acls[] = $this->constructAclActionForRole($additional_action, $role_name);
                }

            }
        }
		return $acls;
	}


	/**
	 * @param $action
	 *
	 * @return xoctAcl
	 */
	protected function constructAclForAction($action) {
		$acl = new xoctAcl();
		$acl->setRole($this->getRole());
		$acl->setAction($action);
		$acl->setAllow(true);

		return $acl;
	}


    /**
     * @param $action
     *
     * @return xoctAcl
     */
    protected function constructAclActionForRole($action, $role) {
        $acl = new xoctAcl();
        $acl->setRole($role);
        $acl->setAction($action);
        $acl->setAllow(true);

        return $acl;
    }


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return String
	 */
	public function getTitle() {
	    global $DIC;
        return $DIC->user()->getLanguage() == 'de' ? $this->title_de : $this->title_en;
	}


    /**
     * @return String
     */
    public function getTitleDE()
    {
        return $this->title_de;
    }

    /**
     * @param String $title_de
     */
    public function setTitleDE($title_de)
    {
        $this->title_de = $title_de;
    }

    /**
     * @return String
     */
    public function getTitleEN()
    {
        return $this->title_en;
    }

    /**
     * @param String $title_en
     */
    public function setTitleEN($title_en)
    {
        $this->title_en = $title_en;
    }

	/**
	 * @return String
	 */
	public function getInfo() {
        global $DIC;
		return $DIC->user()->getLanguage() == 'de' ? $this->info_de : $this->info_en;
	}

    /**
     * @return String
     */
    public function getInfoDE()
    {
        return $this->info_de;
    }

    /**
     * @param String $info_de
     */
    public function setInfoDE($info_de)
    {
        $this->info_de = $info_de;
    }

    /**
     * @return String
     */
    public function getInfoEN()
    {
        return $this->info_en;
    }

    /**
     * @param String $info_en
     */
    public function setInfoEN($info_en)
    {
        $this->info_en = $info_en;
    }

	/**
	 * @return String
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param String $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}


	/**
	 * @return int
	 */
	public function getRead() {
		return $this->read_access;
	}


	/**
	 * @param int $read
	 */
	public function setRead($read) {
		$this->read_access = $read;
	}


	/**
	 * @return int
	 */
	public function getWrite() {
		return $this->write_access;
	}


	/**
	 * @param int $write
	 */
	public function setWrite($write) {
		$this->write_access = $write;
	}


	/**
	 * @return String
	 */
	public function getAdditionalAclActions() {
		return str_replace(' ', '', $this->additional_acl_actions);
	}


	/**
	 * @param String $additional_acl_actions
	 */
	public function setAdditionalAclActions($additional_acl_actions) {
		$this->additional_acl_actions = $additional_acl_actions;
	}


	/**
	 * @return String
	 */
	public function getAdditionalActionsDownload() {
		return $this->additional_actions_download;
	}


	/**
	 * @param String $additional_actions_download
	 */
	public function setAdditionalActionsDownload($additional_actions_download) {
		$this->additional_actions_download = $additional_actions_download;
	}


	/**
	 * @return String
	 */
	public function getAdditionalActionsAnnotate() {
		return $this->additional_actions_annotate;
	}


	/**
	 * @param String $additional_actions_annotate
	 */
	public function setAdditionalActionsAnnotate($additional_actions_annotate) {
		$this->additional_actions_annotate = $additional_actions_annotate;
	}


    /**
     * @return String
     */
    public function getAddedRole() {
        return $this->added_role;
    }


    /**
     * @param String $added_role
     */
    public function setAddedRole($added_role) {
        $this->added_role = $added_role;
    }

    /**
     * @return String
     */
    public function getAddedRoleName() {
        return $this->added_role_name;
    }


    /**
     * @param String $added_role_name
     */
    public function setAddedRoleName($added_role_name) {
        $this->added_role_name = $added_role_name;
    }

    /**
     * @return int
     */
    public function getAddedRoleRead() {
        return $this->added_role_read_access;
    }


    /**
     * @param int $read
     */
    public function setAddedRoleRead($read) {
        $this->added_role_read_access = $read;
    }


    /**
     * @return int
     */
    public function getAddedRoleWrite() {
        return $this->added_role_write_access;
    }


    /**
     * @param int $write
     */
    public function setAddedRoleWrite($write) {
        $this->added_role_write_access = $write;
    }


    /**
     * @return String
     */
    public function getAddedRoleAclActions() {
        return str_replace(' ', '', $this->added_role_acl_actions);
    }


    /**
     * @param String $additional_acl_actions
     */
    public function setAddedRoleAclActions($additional_acl_actions) {
        $this->added_role_acl_actions = $additional_acl_actions;
    }


    /**
     * @return String
     */
    public function getAddedRoleActionsDownload() {
        return $this->added_role_actions_download;
    }


    /**
     * @param String $additional_actions_download
     */
    public function setAddedRoleActionsDownload($additional_actions_download) {
        $this->added_role_actions_download = $additional_actions_download;
    }


    /**
     * @return String
     */
    public function getAddedRoleActionsAnnotate() {
        return $this->added_role_actions_annotate;
    }


    /**
     * @param String $additional_actions_annotate
     */
    public function setAddedRoleActionsAnnotate($additional_actions_annotate) {
        $this->added_role_actions_annotate = $additional_actions_annotate;
    }


	/**
	 * @return int
	 */
	public function getSort() {
		return $this->sort;
	}


	/**
	 * @param int $sort
	 */
	public function setSort($sort) {
		$this->sort = $sort;
	}

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    /**
     * @param int $default
     */
    public function setDefault($default)
    {
        $this->is_default = $default;
    }

}