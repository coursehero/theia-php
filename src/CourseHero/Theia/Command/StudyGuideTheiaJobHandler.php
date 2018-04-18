<?php

namespace CourseHero\TheiaBundle\Command;

use CourseHero\StudyGuideBundle\Constants\StageConstants;
use CourseHero\StudyGuideBundle\Service\StudyGuideConnectionService;
use JMS\Serializer\SerializerBuilder;
use JMS\SerializerBundle\JMSSerializerBundle;
use StudyGuideBlocks\Blocks\Block;
use StudyGuideBlocks\Blocks\CourseBlock;
use StudyGuideBlocks\Blocks\SectionBlock;
use StudyGuideBlocks\Blocks\SubtopicBlock;

class StudyGuideTheiaJobHandler extends TheiaJobHandler
{
    public static $componentLibrary = '@coursehero-components/study-guides';

    /**
     * @param CourseBlock $courseBlock
     * @param Block $blockToRender
     * @return string
     */
    public static function getProps(CourseBlock $courseBlock, Block $blockToRender)
    {
        $jmsSerializer = SerializerBuilder::create()->build();
        return $jmsSerializer->serialize([
            "course" => $courseBlock,
            "route" => $blockToRender->getRoute()
        ], 'json');
    }

    /** @var StudyGuideConnectionService */
    private $studyGuideConnectionService;

    /** @var JMSSerializerBundle */
    private $jmsSerializer;

    public function __construct(\Theia\Client $theiaClient, ReheatCacheJobCreator $jobCreator, StudyGuideConnectionService $studyGuideConnectionService)
    {
        parent::__construct($theiaClient, $jobCreator);
        $this->studyGuideConnectionService = $studyGuideConnectionService;
        $this->jmsSerializer = SerializerBuilder::create()->build();
    }

    /**
     * @param string $builtAt
     * @param string $commitHash
     * @throws \Exception
     */
    public function processNewBuildJob(string $builtAt, string $commitHash)
    {
        $courseBlocks = $this->studyGuideConnectionService->getCoursesTree(StageConstants::STAGE_PUBLISHED, false);
        // TODO this is causing the body to be serialized twice, because it will be serialized again when the message is sent
        // TODO commented out b/c we currently do not even use IndexApp
        // $this->createRenderJob('IndexApp', $this->jmsSerializer->serialize(['courses' => $courseBlocks], 'json'));

        /** @var CourseBlock $courseBlock */
        foreach ($courseBlocks as $courseBlock) {
            // TODO: kevin wants to not do this
            /*
                /sg/intro-to-bio/ => intro-to-bio
            */
            $slug = $courseBlock->getName();
            $slug = rtrim($slug, '/');
            $slug = ltrim($slug, '/sg/');
            $this->createProducerJob($slug);
        }
    }

    /**
     * @param string $producerGroup
     * @throws \Exception
     */
    public function processProducerJob(string $producerGroup)
    {
        $courseTree = $this->studyGuideConnectionService->getCourseTree($producerGroup, StageConstants::STAGE_PUBLISHED, false);

        // course landing view
        $props = self::getProps($courseTree, $courseTree);
        $this->theiaClient->renderAndCache(self::$componentLibrary, 'CourseApp', $props, true);

        /** @var Block $block */
        foreach ($courseTree->createIterator() as $block) {
            if (in_array($block->getBlockType(), [SectionBlock::BLOCK_TYPE, SubtopicBlock::BLOCK_TYPE])) {
                $props = self::getProps($courseTree, $block);
                $this->theiaClient->renderAndCache(self::$componentLibrary, 'CourseApp', $props, true);
            }
        }

        // This is important for when a course is republished. It has no effect when a new build job created this producer job
        $this->studyGuideConnectionService->setCacheForCourse($courseTree);
    }
}
