<?php
namespace site\fe\matter\article;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 单图文
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	public function index_action($siteId) {
		\TPL::output('/site/fe/matter/article/list');
		exit;
	}
	/**
	 * 返回请求的素材
	 *
	 * @param strng $site
	 * @param int $id
	 */
	public function get_action($site, $id) {
		$oUser = $this->who;

		$modelArticle = $this->model('matter\article');
		$oArticle = $modelArticle->byId($id);
		if (false === $oArticle) {
			return new \ObjectNotFoundError();
		}
		/*如果此单图文属于引用那么需要返回被引用的单图文*/
		if ($oArticle->from_mode === 'C') {
			$id2 = $oArticle->from_id;
			$oArticle2 = $modelArticle->byId($id2, ['fields' => 'body,author,siteid,id']);
			$oArticle->body = $oArticle2->body;
			$oArticle->author = $oArticle2->author;
		}
		/* 单图文所属的频道 */
		$oArticle->channels = $this->model('matter\channel')->byMatter($oArticle->id, 'article', ['public_visible' => 'Y']);
		if (count($oArticle->channels) && !isset($oArticle->config->nav->app)) {
			$aNavApps = [];
			foreach ($oArticle->channels as $oChannel) {
				if (!empty($oChannel->config->nav->app)) {
					$aNavApps = array_merge($aNavApps, $oChannel->config->nav->app);
				}
			}
			if (!isset($oArticle->config->nav)) {
				$oArticle->config->nav = new \stdClass;
			}
			$oArticle->config->nav->app = $aNavApps;
		}
		$modelCode = $this->model('code\page');
		foreach ($oArticle->channels as &$channel) {
			if ($channel->style_page_id) {
				$channel->style_page = $modelCode->lastPublishedByName($site, $channel->style_page_name, 'id,html,css,js');
			}
		}
		/* 单图文所属的标签 */
		$tags = [];
		if (!empty($oArticle->matter_cont_tag)) {
			foreach ($oArticle->matter_cont_tag as $key => $tagId) {
				$T = [
					'id,title',
					'xxt_tag',
					['id' => $tagId],
				];
				$tag = $modelArticle->query_obj_ss($T);
				$tags[] = $tag;
			}
		}
		$oArticle->tags = $tags;
		if ($oArticle->has_attachment === 'Y') {
			$oArticle->attachments = $modelArticle->query_objs_ss(
				array(
					'*',
					'xxt_matter_attachment',
					['matter_id' => $id, 'matter_type' => 'article'],
				)
			);
		}
		if ($oArticle->custom_body === 'Y' && $oArticle->page_id) {
			/* 定制页 */
			$modelCode = $this->model('code\page');
			$oArticle->page = $modelCode->lastPublishedByName($oArticle->siteid, $oArticle->body_page_name);
		}
		$aData = array();
		$aData['article'] = $oArticle;
		$aData['user'] = $oUser;
		/* 站点信息 */
		if ($oArticle->use_site_header === 'Y' || $oArticle->use_site_footer === 'Y') {
			$oSite = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		} else {
			$oSite = $this->model('site')->byId($site);
		}
		$aData['site'] = $oSite;
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $userAgent)) {
			$oSite->yx = $this->model('sns\yx')->bySite($oSite->id, 'cardname,cardid');
		}
		/*项目页面设置*/
		if ($oArticle->use_mission_header === 'Y' || $oArticle->use_mission_footer === 'Y') {
			if ($oArticle->mission_id) {
				$aData['mission'] = $this->model('matter\mission')->byId(
					$oArticle->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}

		return new \ResponseData($aData);
	}
	/**
	 *
	 */
	public function list_action($site, $tagid, $page = 1, $size = 10) {
		$model = $this->model('matter\article');

		$user = $this->who;

		$options = new \stdClass;
		$options->tag = array($tagid);

		$result = $model->find($site, $user, $page, $size, $options);

		return new \ResponseData($result);
	}
	/**
	 * 下载附件
	 */
	public function attachmentGet_action($site, $articleid, $attachmentid) {
		if (empty($site) || empty($articleid) || empty($attachmentid)) {
			die('没有指定有效的附件');
		}

		$user = $this->who;
		/**
		 * 访问控制
		 */
		$modelArticle = $this->model('matter\article');
		$oArticle = $modelArticle->byId($articleid);
		$this->checkDownloadRule($oArticle, true);
		/**
		 * 获取附件
		 */
		$q = [
			'*',
			'xxt_matter_attachment',
			['matter_id' => $articleid, 'matter_type' => 'article', 'id' => $attachmentid],
		];
		if (false === ($att = $modelArticle->query_obj_ss($q))) {
			die('指定的附件不存在');
		}
		/**
		 * 记录日志
		 */
		$modelArticle->update("update xxt_article set download_num=download_num+1 where id='$articleid'");
		$log = [
			'userid' => $user->uid,
			'nickname' => $user->nickname,
			'download_at' => time(),
			'siteid' => $site,
			'article_id' => $articleid,
			'attachment_id' => $attachmentid,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'client_ip' => $this->client_ip(),
		];
		$modelArticle->insert('xxt_article_download_log', $log, false);

		if (strpos($att->url, 'alioss') === 0) {
			$fsAlioss = \TMS_APP::M('fs/alioss', $this->siteId, '_attachment');
			$downloadUrl = $fsAlioss->getHostUrl() . '/' . $site . '/_attachment/article/' . $articleid . '/' . urlencode($att->name);
			$this->redirect($downloadUrl);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $site, '附件');
			//header("Content-Type: application/force-download");
			header("Content-Type: $att->type");
			header("Content-Disposition: attachment; filename=" . $att->name);
			header('Content-Length: ' . $att->size);
			echo $fs->read(str_replace('local://', '', $att->url));
		} else {
			$fs = $this->model('fs/saestore', $site);
			//header("Content-Type: application/force-download");
			header("Content-Type: $att->type");
			header("Content-Disposition: attachment; filename=" . $att->name);
			header('Content-Length: ' . $att->size);
			echo $fs->read($att->url);
		}

		exit;
	}
	/**
	 * 检查参与规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	private function checkDownloadRule($oApp, $bRedirect = false) {
		if (!isset($oApp->downloadRule->scope)) {
			return [true];
		}
		$oUser = $this->who;
		$oDownloadRule = $oApp->downloadRule;
		$oScope = $oDownloadRule->scope;

		if (isset($oScope->member) && $oScope->member === 'Y') {
			if (empty($oDownloadRule->optional->member) || $oDownloadRule->optional->member !== 'Y') {
				if (!isset($oDownloadRule->member)) {
					$msg = '需要填写通讯录信息，请联系活动的组织者解决。';
					if (true === $bRedirect) {
						$this->outputInfo($msg);
					} else {
						return [false, $msg];
					}
				}
				$aResult = $this->enterAsMember2($oApp);
				/**
				 * 限通讯录用户访问
				 * 如果指定的任何一个通讯录要求用户关注公众号，但是用户还没有关注，那么就要求用户先关注公众号，再填写通讯录
				 */
				if (false === $aResult[0]) {
					if (true === $bRedirect) {
						$aMemberSchemaIds = [];
						$modelMs = $this->model('site\user\memberschema');
						foreach ($oDownloadRule->member as $mschemaId => $oRule) {
							$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
							if ($oMschema) {
								if ($oMschema->is_wx_fan === 'Y') {
									$oApp2 = clone $oApp;
									$oApp2->downloadRule = new \stdClass;
									$oApp2->downloadRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
									$aResult = $this->checkSnsEntryRule2($oApp2, $bRedirect);
									if (false === $aResult[0]) {
										return $aResult;
									}
								}
								$aMemberSchemaIds[] = $mschemaId;
							}
						}
						$this->gotoMember($oApp, $aMemberSchemaIds);
					} else {
						$msg = '您没有填写通讯录信息，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
						return [false, $msg];
					}
				}
			}
		}
		if (isset($oScope->sns) && $oScope->sns === 'Y') {
			$aResult = $this->checkSnsEntryRule2($oApp, $bRedirect);
			if (false === $aResult[0]) {
				return $aResult;
			}
		}
		if (isset($oScope->group) && $oScope->group === 'Y') {
			if (empty($oDownloadRule->optional->group) || $oDownloadRule->optional->group !== 'Y') {
				$bMatched = false;
				/* 限分组用户访问 */
				if (isset($oDownloadRule->group->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oDownloadRule->group->id, ['fields' => 'id,state,title']);
					if ($oGroupApp && $oGroupApp->state === '1') {
						$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);
						if (count($oGroupUsr)) {
							$oGroupUsr = $oGroupUsr[0];
							if (isset($oDownloadRule->group->round->id)) {
								if ($oGroupUsr->round_id === $oDownloadRule->group->round->id) {
									$bMatched = true;
								}
							} else {
								$bMatched = true;
							}
						}
					}
				}
				if (false === $bMatched) {
					$msg = '您目前的分组，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					if (true === $bRedirect) {
						$this->outputInfo($msg);
					} else {
						return [false, $msg];
					}
				}
			}
		}

		return [true];
	}
	/*
	 *
	 */
	private function enterAsMember2($oApp) {
		if (!isset($oApp->downloadRule->member)) {
			return [false];
		}

		$oDownloadRule = $oApp->downloadRule;
		$oUser = $this->who;
		$bMatched = false;
		$bMatchedRule = null;

		/* 检查用户是否已经关注公众号 */
		$fnCheckSnsFollow = function ($mschemaId, $oOriginalMatter) {
			$bPassed = true;
			$modelMs = $this->model('site\user\memberschema');
			$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
			if ($oMschema->is_wx_fan === 'Y') {
				$oApp2 = clone $oOriginalMatter;
				$oApp2->downloadRule = new \stdClass;
				$oApp2->downloadRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
				$aResult = $this->enterAsSns2($oApp2);
				if (false === $aResult[0]) {
					$bPassed = false;
				}
			}

			return $bPassed;
		};

		foreach ($oDownloadRule->member as $schemaId => $rule) {
			/* 检查用户的信息是否完整，是否已经通过审核 */
			$modelMem = $this->model('site\user\member');
			$modelAcnt = $this->model('site\user\account');
			if (empty($oUser->unionid)) {
				$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => 'unionid']);
				if ($oSiteUser && !empty($oSiteUser->unionid)) {
					$unionid = $oSiteUser->unionid;
				}
			} else {
				$unionid = $oUser->unionid;
			}
			if (empty($unionid)) {
				$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
				if (count($aMembers) === 1) {
					$oMember = $aMembers[0];
					if ($oMember->verified === 'Y') {
						if ($fnCheckSnsFollow($schemaId, $oApp)) {
							$bMatched = true;
							$bMatchedRule = $rule;
							break;
						}
					}
				}
			} else {
				$aUnionUsers = $modelAcnt->byUnionid($unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
				foreach ($aUnionUsers as $oUnionUser) {
					$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
					if (count($aMembers) === 1) {
						$oMember = $aMembers[0];
						if ($oMember->verified === 'Y') {
							if ($fnCheckSnsFollow($schemaId, $oApp)) {
								$bMatched = true;
								$bMatchedRule = $rule;
								break;
							}
						}
					}
				}
				if ($bMatched) {
					break;
				}
			}
		}

		return [$bMatched, $bMatchedRule];
	}
	/**
	 * 限社交网站用户参与
	 */
	private function enterAsSns2($oMatter) {
		$oDownloadRule = $oMatter->downloadRule;
		$oUser = $this->who;
		$bFollowed = false;
		$oFollowedRule = null;

		/* 检查用户是否已经关注公众号 */
		$fnCheckSnsFollow = function ($snsName, $matterSiteId, $openid) {
			if ($snsName === 'wx') {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($matterSiteId)) && $wxConfig->joined === 'Y') {
					$snsSiteId = $matterSiteId;
				} else {
					$snsSiteId = 'platform';
				}
			} else {
				$snsSiteId = $matterSiteId;
			}
			// 检查用户是否已经关注
			$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
			if ($modelSnsUser->isFollow($snsSiteId, $openid)) {
				return true;
			}

			return false;
		};

		foreach ($oDownloadRule->sns as $snsName => $rule) {
			if (isset($oUser->sns->{$snsName})) {
				/* 缓存的信息 */
				$snsUser = $oUser->sns->{$snsName};
				if ($fnCheckSnsFollow($snsName, $oMatter->siteid, $snsUser->openid)) {
					$bFollowed = true;
					$oFollowedRule = $rule;
					break;
				}
			} else {
				$modelAcnt = $this->model('site\user\account');
				$propSnsOpenid = $snsName . '_openid';
				if (empty($oUser->unionid)) {
					/* 当前站点用户绑定的信息 */
					$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => $propSnsOpenid]);
					if ($oSiteUser && $fnCheckSnsFollow($snsName, $oMatter->siteid, $oSiteUser->{$propSnsOpenid})) {
						$bFollowed = true;
						$oFollowedRule = $rule;
						break;
					}
				} else {
					/* 当前注册用户绑定的信息 */
					$aSiteUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oMatter->siteid, 'fields' => $propSnsOpenid]);
					foreach ($aSiteUsers as $oSiteUser) {
						$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => $propSnsOpenid]);
						if ($oSiteUser && $fnCheckSnsFollow($snsName, $oMatter->siteid, $oSiteUser->{$propSnsOpenid})) {
							$bFollowed = true;
							$oFollowedRule = $rule;
							break;
						}
					}
					if ($bFollowed) {
						break;
					}
				}
			}
		}

		return [$bFollowed, $oFollowedRule];
	}
	/**
	 * 检查是否已经关注公众号
	 */
	private function checkSnsEntryRule22($oMatter, $bRedirect) {
		$aResult = $this->enterAsSns2($oMatter);
		if (false === $aResult[0]) {
			$msg = '您没有关注公众号，不满足【' . $oMatter->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
			if (true === $bRedirect) {
				$oDownloadRule = $oMatter->downloadRule;
				if (!empty($oDownloadRule->sns->wx->entry)) {
					/* 通过邀请链接访问 */
					if (!empty($_GET['inviteToken'])) {
						$oMatter->params = new \stdClass;
						$oMatter->params->inviteToken = $_GET['inviteToken'];
					}
					$this->snsWxQrcodeFollow($oMatter);
				} else if (!empty($oDownloadRule->sns->qy->entry)) {
					$this->snsFollow($oMatter->siteid, 'qy', $oMatter);
				} else if (!empty($oDownloadRule->sns->yx->entry)) {
					$this->snsFollow($oMatter->siteid, 'yx', $oMatter);
				} else {
					$this->outputInfo($msg);
				}
			} else {
				return [false, $msg];
			}
		}

		return [true];
	}
}