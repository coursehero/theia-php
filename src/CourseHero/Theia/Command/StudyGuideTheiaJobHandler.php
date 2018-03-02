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
        $courseBlocks = $this->studyGuideConnectionService->getCoursesTree(StageConstants::STAGE_PUBLISHED);
        $this->createRenderJob('IndexApp', $this->jmsSerializer->serialize(['courses' => $courseBlocks], 'json'));

        /** @var CourseBlock $courseBlock */
        foreach ($courseBlocks as $courseBlock) {
            $this->createProducerJob($courseBlock->getName());
        }
    }

    /**
     * @param string $producerGroup
     * @throws \Exception
     */
    public function processProducerJob(string $producerGroup)
    {
        $courseTree = $this->studyGuideConnectionService->getCourseTree($producerGroup, StageConstants::STAGE_PUBLISHED);
        /** @var Block $block */
        foreach ($courseTree->createIterator() as $block) {
            if (in_array($block->getBlockType(), [SectionBlock::BLOCK_TYPE, SubtopicBlock::BLOCK_TYPE])) {
                $props = $this->jmsSerializer->serialize(["course" => $courseTree, "location" => $this->route($block)], 'json');
                $this->theiaClient->renderAndCache(self::$componentLibrary, 'CourseApp', $props, true);
            }
        }
    }

    /**
     * Needs to match implementation in components/study-guides/src/utils/route.js
     *
     * @param Block $block
     * @return string
     */
    private function route(Block $block): string
    {
        $slug = function (string $string): string {
            $removePatterns = [
                '\?',
                // '\(',
                // '\)',
                ',',
                '\/'
            ];
            $removePatternsRegex = '/' . join('|', $removePatterns) . '/';
            $spacesReplaced = preg_replace('/\s/', '-', $string);
            $charsRemoved = preg_replace($removePatternsRegex, '', $spacesReplaced);
            return strtolower($charsRemoved);
        };

        $createUrl = function (string $courseSlug, string $subtopicSlug, string $sectionSlug): string {
            if ($sectionSlug === 'overview') {
                $sectionSlug = '';
            }
            $parts = array_filter(['study-guides', $courseSlug, $subtopicSlug, $sectionSlug]);
            return '/' . join('/', $parts) . '/';
        };

        $blockLevels = [$block];
        while ($block->getParent()) {
            $block = $block->getParent();
            array_unshift($blockLevels, $block);
        }

        $courseSlug = $slug($blockLevels[0]->getName());
        $subtopicSlug = isset($blockLevels[2]) ? $slug($blockLevels[2]->getName()) : '';
        $sectionSlug = isset($blockLevels[3]) ? $slug($blockLevels[3]->getName()) : '';

        return $createUrl($courseSlug, $subtopicSlug, $sectionSlug);
    }
}
