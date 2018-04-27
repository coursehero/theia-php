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

    /** @var array */
    protected $courseTrees = [];

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

    /**
     * @param CourseBlock $courseBlock
     * @param string $urlPath
     * @return Block
     */
    public static function findMatchingBlock(CourseBlock $courseBlock, string $route): Block
    {
        foreach ($courseBlock->createIterator() as $block) {
            if ($block->getRoute() === $route) {
                return $block;
            }
        }

        throw new NotFoundHttpException("Study Guide for route $route does not exist");
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
            $slug = $courseBlock->getRoute();
            $slug = rtrim($slug, '/');
            $slug = ltrim($slug, '/sg/');
            $this->createProducerJob($slug, []);
        }
    }

    /**
     * @param string $producerGroup - courseSlug
     * @param array $jobParams
     * @throws \Exception
     */
    public function processProducerJob(string $producerGroup, array $jobParams)
    {
        $courseTree = $this->getCourseTree($producerGroup, true);

        // course landing view
        $this->createCustomRenderJob('CourseApp', $producerGroup, $courseTree);

        /** @var Block $block */
        foreach ($courseTree->createIterator() as $block) {
            if (in_array($block->getBlockType(), [SectionBlock::BLOCK_TYPE, SubtopicBlock::BLOCK_TYPE])) {
                $this->createCustomRenderJob('CourseApp', $producerGroup, $block);
            }
        }

        // This is important for when a course is republished. It has no effect when a new build job created this producer job
        $this->studyGuideConnectionService->setCacheForCourse($courseTree);
    }

    /**
     * Grabs a course and adds it to our local course tree cache
     */
    protected function getCourseTree(string $courseSlug, bool $force = false): CourseBlock
    {
        if ($force || !isset($this->courseTrees[$courseSlug])) {
            $this->courseTrees[$courseSlug] = $this->studyGuideConnectionService->getCourseTree($courseSlug, StageConstants::STAGE_PUBLISHED, false);
        }

        return $this->courseTrees[$courseSlug];
    }

    protected function createCustomRenderJob(string $component, string $courseSlug, Block $block)
    {
        $data = json_encode([
            'courseSlug' => $courseSlug,
            'route' => $block->getRoute()
        ]);
        $this->createRenderJob($component, $data);
    }

    // The props needed are too big to store in an SQS message, so we override the render job
    // and provide the props from an in-memory cache
    public function processRenderJob(string $component, string $props)
    {
        $data = json_decode($props);
        $courseSlug = $data['courseSlug'];
        $route = $data['route'];

        $courseTree = $this->getCourseTree($courseSlug);
        $block = self::findMatchingBlock($courseTree, $route);
        parent::processRenderJob('CourseApp', self::getProps($courseTree, $block));
    }
}
