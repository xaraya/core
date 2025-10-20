<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\UserApi;
use Xaraya\Modules\DynamicData\UtilApi;
use BadParameterException;
use ConfigurationException;
use DataObjectLinks;
use DataStoreLinks;
use Exception;
use xarCore;
use xarCurl;
use xarModItemVars;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin relations function
 * @extends MethodClass<AdminGui>
 */
class RelationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return relationship information (test only)
     * @see AdminGui::relations()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $this->var()->check('module', $module);
        $this->var()->check('module_id', $module_id);
        $this->var()->check('itemtype', $itemtype);
        $this->var()->check('objectid', $objectid);
        $this->var()->check('table', $table);
        $this->var()->check('field', $field);
        $this->var()->check('value', $value);
        $this->var()->check('relation', $relation);
        $this->var()->check('direction', $direction);
        $this->var()->check('withobjectid', $withobjectid);
        $this->var()->check('withtable', $withtable);
        $this->var()->check('withfield', $withfield);
        $this->var()->check('withvalue', $withvalue);
        $this->var()->check('confirm', $confirm);
        $this->var()->check('update', $update);
        $this->var()->check('delete', $delete);
        $this->var()->check('what', $what);
        $this->var()->check('extra', $extra);

        // filter out invalid tables
        $xartables =  $this->db()->getTables();
        if (!empty($table)) {
            if ($table == 'dummy' || substr($table, 0, 15) == 'module variable') {
                $table = null;
            } elseif ($table == 'dynamic_data') {
                $table = $xartables['dynamic_data'];
            }
        }

        // prepare template variables
        $data = [
            'module_id' => $module_id,
            'itemtype' => $itemtype,
            'objectid' => $objectid,
            'table' => $table,
            'field' => $field,
            'value' => $value,
            'relation' => $relation,
            'direction' => $direction,
            'withobjectid' => $withobjectid,
            'withtable' => $withtable,
            'withfield' => $withfield,
            'withvalue' => $withvalue,
            'extra' => $extra,
        ];

        // get objects
        $data['objects'] = $userapi->getobjects();

        // import the DataObjectLinks class
        sys::import('modules.dynamicdata.class.objects.links');

        // get linktypes
        $data['linktypes'] = DataObjectLinks::$linktypes;

        // get tables
        $dbconn = $this->db()->getConn();
        $dbInfo = $dbconn->getDatabaseInfo();
        // Pass the full info object to the template, let them figure out how and what
        $data['tables'] = $dbInfo->getTables();

        // get mapping of objects to datasources by looking at property sources
        if (empty($objectid) && empty($table)) {
            $data['mapping'] = DataObjectLinks::getMapping();
        }

        //dynamicdata_sync_relations();

        if (!empty($objectid)) {
            $object = $this->data()->getObject(['objectid' => $objectid]);
            if (!$object->checkAccess('config')) {
                $msg = $this->ml('Configure #(1) is forbidden', $object->label);
                return $this->ctl()->forbidden($msg);
            }
            $data['object'] = $object;
            $data['fields'] = $object->properties;

            $this->tpl()->setPageTitle($this->ml('Links for #(1)', $object->label));

            // get all links, including 'info' for reverse one-way information
            $links = DataObjectLinks::getLinks($object, 'all');
            if (!empty($links[$object->name])) {
                $data['relations'] = $links[$object->name];
            } else {
                $data['relations'] = [];
            }
            // FIXME: remove initialization of modvar after next release
            $this->mod()->setVar('getlinkedobjects', 0);

            $data['yumlspec'] = '';
            $data['yumlpath'] = '';
            if (!empty($data['relations'])) {
                $yuml_spec = '[' . $object->label;

                /* Add the properties to the class diagram
                    $proptypes = $this->prop()->getPropertyTypes();
                    $join = '|';
                    foreach ($object->properties as $property) {
                        $yuml_spec .= $join . $property->name . ': ' . $proptypes[$property->type]['name'];
                        if ($property->defaultvalue !== '') {
                            $yuml_spec .= ' = ' . $property->defaultvalue;
                        }
                        $join = ';';
                    }
                */
                $yuml_spec .= ']';

                $name2label = [];
                foreach ($data['objects'] as $info) {
                    $name2label[$info['name']] = $info['label'];
                }

                foreach ($data['relations'] as $link) {
                    // in case we have links with unknown objects
                    if (empty($name2label[$link['target']])) {
                        $name2label[$link['target']] = $link['target'];
                    }
                    if ($link['link_type'] == 'parents') {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['to_prop'] . '-' . $link['from_prop'] . ' *[' . $object->label . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['to_prop'] . '-' . $link['from_prop'] . ' *>[' . $object->label . ']';
                        } else {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['to_prop'] . '-' . $link['from_prop'] . ' *>[' . $object->label . ']';
                        }
                    } elseif ($link['link_type'] == 'linkedfrom' && $link['target'] != $object->name) {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '[' . $object->label . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '>[' . $object->label . ']';
                        } else {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '>[' . $object->label . ']';
                        }
                    } elseif ($link['link_type'] == 'extended' && $link['target'] != $object->name) {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']^-[' . $object->label . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']^-.-[' . $object->label . ']';
                        } else {
                            $yuml_spec .= ', [' . $name2label[$link['target']] . ']^-.-[' . $object->label . ']';
                        }
                    } elseif ($link['link_type'] == 'extensions') {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $object->label . ']^-[' . $name2label[$link['target']] . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $object->label . ']^-.-[' . $name2label[$link['target']] . ']';
                        } else {
                            $yuml_spec .= ', [' . $object->label . ']^-.-[' . $name2label[$link['target']] . ']';
                        }
                    } elseif ($link['link_type'] == 'linkedto') {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '[' . $name2label[$link['target']] . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '>[' . $name2label[$link['target']] . ']';
                        } else {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . '>[' . $name2label[$link['target']] . ']';
                        }
                    } elseif ($link['link_type'] == 'children' && $link['target'] != $object->name) {
                        if ($link['direction'] == 'bi') {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . ' *[' . $name2label[$link['target']] . ']';
                        } elseif ($link['direction'] == 'uni') {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . ' *>[' . $name2label[$link['target']] . ']';
                        } else {
                            $yuml_spec .= ', [' . $object->label . ']' . $link['from_prop'] . '-' . $link['to_prop'] . ' *>[' . $name2label[$link['target']] . ']';
                        }
                    }
                }

                // CHECKME: what if var/processes is not under the web root anymore ?
                if (is_writable(sys::varpath() . '/processes/')) {
                    $yuml_hash = md5($yuml_spec);
                    // CHECKME: what if var/processes is not under the web root anymore ?
                    $filepath = sys::varpath() . '/processes/yuml-' . $yuml_hash . '.png';
                    if (!file_exists($filepath)) {
                        $yuml_url = 'http://yuml.me/diagram/class/' . rawurlencode($yuml_spec);
                        // chris: file_get_contents requires allow_url_fopen=1 in php.ini
                        // added support for retrieval using curl when available
                        try {
                            sys::import('modules.base.class.xarCurl');
                            $curl = new xarCurl(['url' => $yuml_url]);
                            if ($curl->errno <> 0) {
                                throw new BadParameterException(
                                    [$yuml_url, $curl->error],
                                    'cURL could not retrieve the file at #(1). Failed with error #(2)'
                                );
                            } else {
                                $curl->seturl($yuml_url);
                                $image = $curl->exec();
                            }
                        } catch (Exception $e) {
                            // check for allow url fopen if curl returned an error
                            if (!xarCore::funcIsDisabled('ini_set')) {
                                ini_set('allow_url_fopen', 1);
                            }
                            if (!ini_get('allow_url_fopen')) {
                                throw new ConfigurationException(
                                    ['allow_url_fopen', 'cURL'],
                                    'PHP is not currently configured to allow URL retrieval of remote files. You must either enable #(1) in php.ini (not recommended) or install the #(2) module for your server, if available.'
                                );
                            } else {
                                $image = file_get_contents($yuml_url);
                            }
                        }
                        if (!empty($image)) {
                            file_put_contents($filepath, $image);
                            $data['yumlpath'] = $filepath;
                        } else {
                            $data['yumlspec'] = $yuml_spec;
                        }
                    } else {
                        $data['yumlpath'] = $filepath;
                    }
                } else {
                    $data['yumlspec'] = $yuml_spec;
                }
            }

            if (!empty($withobjectid)) {
                $withobject = $this->data()->getObject(['objectid' => $withobjectid]);
                $data['withobject'] = $withobject;
                $data['withfields'] = $withobject->properties;
            }
            if (!empty($confirm)) {
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                /* no longer in use (for now ?)
                if (!empty($value)) {
                    $field = $value;
                }
                if (!empty($withvalue)) {
                    $withfield = $withvalue;
                }
                */
                if (empty($direction)) {
                    $direction = 'bi';
                }
                if (empty($extra)) {
                    $extra = '';
                }

                // add link
                DataObjectLinks::addLink($objectid, $field, $withobjectid, $withfield, $relation, $direction, $extra);
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'relations',
                    ['objectid' => $objectid]
                ));
                return true;

            } elseif (!empty($delete) && !empty($what)) {
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                // remove selected link(s)
                foreach ($what as $link_id => $val) {
                    if (empty($link_id) || empty($val)) {
                        continue;
                    }
                    DataObjectLinks::removeLink($link_id);
                }
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'relations',
                    ['objectid' => $objectid]
                ));
                return true;

            } elseif (!empty($update)) {
                $this->var()->check('getlinkedobjects', $getlinkedobjects);
                if (!empty($getlinkedobjects)) {
                    xarModItemVars::set('dynamicdata', 'getlinkedobjects', 1, $objectid);
                } else {
                    xarModItemVars::set('dynamicdata', 'getlinkedobjects', 0, $objectid);
                }
            }

            // get fieldtype property to show object properties
            $data['prop'] = $this->prop()->getProperty([
                'type' => 'fieldtype',
                'name' => 'dummy',
            ]);

        } elseif (!empty($table)) {
            // set context if available in function
            $object = $this->data()->getObject(['table' => $table]);
            if (!$object->checkAccess('config')) {
                $msg = $this->ml('Configure #(1) is forbidden', $object->label);
                return $this->ctl()->forbidden($msg);
            }
            $data['fields'] = $object->properties;

            $this->tpl()->setPageTitle($this->ml('Links for #(1)', $object->label));

            sys::import('modules.dynamicdata.class.datastores.links');

            // get all links, including 'info' for reverse one-way information
            $links = DataStoreLinks::getLinks($table, 'all');
            if (!empty($links[$table])) {
                $data['relations'] = $links[$table];
            } else {
                $data['relations'] = [];
            }

            // get foreign keys for tables
            $data['foreignkeys'] = DataStoreLinks::getForeignKeys();

            if (!empty($withtable)) {
                $withobject = $this->data()->getObject(['table' => $withtable]);
                $data['withfields'] = $withobject->properties;
            }
            if (!empty($confirm)) {
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                /* no longer in use (for now ?)
                if (!empty($value)) {
                    $field = $value;
                }
                if (!empty($withvalue)) {
                    $withfield = $withvalue;
                }
                */
                if (empty($direction)) {
                    $direction = 'bi';
                }
                if (empty($extra)) {
                    $extra = '';
                }
                // CHECKME: always bi-directional for tables ?
                $direction = 'bi';
                DataStoreLinks::addLink($table, $field, $withtable, $withfield, $relation, $direction, $extra);
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'relations',
                    ['table' => $table]
                ));
                return true;

            } elseif (!empty($delete) && !empty($what)) {
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                // remove selected link(s)
                foreach ($what as $link_id => $val) {
                    if (empty($link_id) || empty($val)) {
                        continue;
                    }
                    DataStoreLinks::removeLink($link_id);
                }
                $this->ctl()->redirect($this->mod()->getURL(
                    'admin',
                    'relations',
                    ['table' => $table]
                ));
                return true;
            }

            // get fieldtype property to show table fields
            $data['prop'] = $this->prop()->getProperty([
                'type' => 'fieldtype',
                'name' => 'dummy',
            ]);

        } elseif (!empty($module_id)) {
            $data['module'] = $this->mod()->getName($module_id);
            // (try to) get the relationships between this module and others
            $data['relations'] = $utilapi->getrelations(['module_id' => $module_id,
                'itemtype' => $itemtype]);
        } else {
            $this->tpl()->setPageTitle($this->ml('Links'));
        }

        if (!isset($data['relations']) || $data['relations'] == false) {
            $data['relations'] = [];
        }

        $this->tpl()->setPageTemplateName('admin');

        return $data;
    }
}
