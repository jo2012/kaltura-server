<?php
/**
 * Enable ad cue point objects management on entry objects
 * @package plugins.adCuePoint
 */
class AdCuePointPlugin extends KalturaPlugin implements IKalturaCuePoint
{
	const PLUGIN_NAME = 'adCuePoint';
	const CUE_POINT_VERSION_MAJOR = 1;
	const CUE_POINT_VERSION_MINOR = 0;
	const CUE_POINT_VERSION_BUILD = 0;
	const CUE_POINT_NAME = 'cuePoint';
	
	/* (non-PHPdoc)
	 * @see IKalturaPlugin::getPluginName()
	 */
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaPermissions::isAllowedPartner()
	 */
	public static function isAllowedPartner($partnerId)
	{
		$partner = PartnerPeer::retrieveByPK($partnerId);
		return $partner->getPluginEnabled(self::PLUGIN_NAME);
	}

	/* (non-PHPdoc)
	 * @see IKalturaEnumerator::getEnums()
	 */
	public static function getEnums($baseEnumName = null)
	{
		if(is_null($baseEnumName))
			return array('AdCuePointType');
	
		if($baseEnumName == 'CuePointType')
			return array('AdCuePointType');
			
		return array();
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaPending::dependsOn()
	 */
	public static function dependsOn()
	{
		$cuePointVersion = new KalturaVersion(
			self::CUE_POINT_VERSION_MAJOR,
			self::CUE_POINT_VERSION_MINOR,
			self::CUE_POINT_VERSION_BUILD);
			
		$dependency = new KalturaDependency(self::CUE_POINT_NAME, $cuePointVersion);
		return array($dependency);
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaObjectLoader::loadObject()
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		if($baseClass == 'KalturaCuePoint' && $enumValue == self::getCuePointTypeCoreValue(AdCuePointType::AD))
			return new KalturaAdCuePoint();
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaObjectLoader::getObjectClass()
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		if($baseClass == 'CuePoint' && $enumValue == self::getCuePointTypeCoreValue(AdCuePointType::AD))
			return 'AdCuePoint';
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaSchemaContributor::contributeToSchema()
	 */
	public static function contributeToSchema($type)
	{
		$coreType = kPluginableEnumsManager::apiToCore('SchemaType', $type);
		if(
			$coreType != SchemaType::SYNDICATION
			&&
			$coreType != CuePointPlugin::getSchemaTypeCoreValue(CuePointSchemaType::SERVE_API)
			&&
			$coreType != CuePointPlugin::getSchemaTypeCoreValue(CuePointSchemaType::INGEST_API)
		)
			return null;
			
		$xsd = '		
		
	<!-- ' . self::getPluginName() . ' -->
		
	<xs:complexType name="T_scene_adCuePoint">
		<xs:complexContent>
			<xs:extension base="T_scene">
				<xs:sequence>
					<xs:element name="sceneEndTime" minOccurs="1" maxOccurs="1" type="xs:time" />
					<xs:element name="sceneTitle" minOccurs="0" maxOccurs="1" type="xs:string" />
					<xs:element name="sourceUrl" minOccurs="0" maxOccurs="1" type="xs:string" />
					<xs:element name="adType" minOccurs="0" maxOccurs="1" type="KalturaAdType" />
					<xs:element name="protocolType" minOccurs="0" maxOccurs="1" type="KalturaAdProtocolType" />
					
					<xs:element ref="scene-extension" minOccurs="0" maxOccurs="unbounded" />
				</xs:sequence>
			</xs:extension>
		</xs:complexContent>
	</xs:complexType>
	
	<xs:element name="scene-ad-cue-point" type="T_scene_adCuePoint" substitutionGroup="scene" />
		';
		
		return $xsd;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaCuePoint::getCuePointTypeCoreValue()
	 */
	public static function getCuePointTypeCoreValue($valueName)
	{
		$value = self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
		return kPluginableEnumsManager::apiToCore('CuePointType', $value);
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaCuePoint::getApiValue()
	 */
	public static function getApiValue($valueName)
	{
		return self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaCuePointXmlParser::getApiValue()
	 */
	public static function parseXml(SimpleXMLElement $scene, $partnerId, CuePoint $cuePoint = null)
	{
		if($scene->getName() != 'scene-ad-cue-point')
			return $cuePoint;
			
		if(!$cuePoint)
			$cuePoint = kCuePointManager::parseXml($scene, $partnerId, new AdCuePoint());
			
		if(!($cuePoint instanceof AdCuePoint))
			return null;
		
		$cuePoint->setEndTime(kXml::timeToInteger($scene->sceneEndTime));
		if(isset($scene->sceneTitle))
			$cuePoint->setName($scene->sceneTitle);
		if(isset($scene->sourceUrl))
			$cuePoint->setSourceUrl($scene->sourceUrl);
		if(isset($scene->adType))
			$cuePoint->setAdType($scene->adType);
		if(isset($scene->protocolType))
			$cuePoint->setSubType($scene->protocolType);
		
		return $cuePoint;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaCuePointXmlParser::generateXml()
	 */
	public static function generateXml(CuePoint $cuePoint, SimpleXMLElement $scenes, SimpleXMLElement $scene = null)
	{
		if(!($cuePoint instanceof AdCuePoint))
			return $scene;
			
		if(!$scene)
			$scene = kCuePointManager::generateCuePointXml($cuePoint, $scenes->addChild('scene-ad-cue-point'));
			
		$scene->addChild('sceneEndTime', kXml::integerToTime($cuePoint->getEndTime()));
		if($cuePoint->getName())
			$scene->addChild('sceneTitle', kMrssManager::stringToSafeXml($cuePoint->getName()));
		if($cuePoint->getSourceUrl())
			$scene->addChild('sourceUrl', $cuePoint->getSourceUrl());
		if($cuePoint->getAdType())
			$scene->addChild('adType', $cuePoint->getAdType());
		if($cuePoint->getSubType())
			$scene->addChild('protocolType', $cuePoint->getSubType());
			
		return $scene;
	}
}
