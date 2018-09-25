## Table of contents

- [\CourseHero\Theia\CachingInterface (interface)](#interface-courseherotheiacachinginterface)
- [\CourseHero\Theia\RenderResult](#class-courseherotheiarenderresult)
- [\CourseHero\Theia\Client](#class-courseherotheiaclient)
- [\CourseHero\Theia\ReheatCache\JobCreator (abstract)](#class-courseherotheiareheatcachejobcreator-abstract)
- [\CourseHero\Theia\ReheatCache\JobProcessor](#class-courseherotheiareheatcachejobprocessor)
- [\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)
- [\CourseHero\Theia\ReheatCache\JobHandler (abstract)](#class-courseherotheiareheatcachejobhandler-abstract)

<hr />

### Interface: \CourseHero\Theia\CachingInterface

> Interface CachingInterface

| Visibility | Function |
|:-----------|:---------|
| public | <strong>get(</strong><em>\string</em> <strong>$key</strong>)</strong> : <em>\CourseHero\Theia\?RenderResult</em> |
| public | <strong>set(</strong><em>\string</em> <strong>$componentLibrary</strong>, <em>\string</em> <strong>$component</strong>, <em>\string</em> <strong>$key</strong>, <em>[\CourseHero\Theia\RenderResult](#class-courseherotheiarenderresult)</em> <strong>$renderResult</strong>, <em>int/\integer</em> <strong>$secondsUntilExpires</strong>)</strong> : <em>void</em> |

<hr />

### Class: \CourseHero\Theia\RenderResult

> Class RenderResult

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>\string</em> <strong>$html</strong>, <em>array</em> <strong>$assets</strong>)</strong> : <em>void</em><br /><em>RenderResult constructor.</em> |
| public | <strong>getAssets()</strong> : <em>array</em> |
| public | <strong>getHtml()</strong> : <em>string</em> |
| public | <strong>isRetrievedFromCache()</strong> : <em>bool</em> |
| public | <strong>setRetrievedFromCache(</strong><em>bool/\boolean</em> <strong>$retrievedFromCache</strong>)</strong> : <em>void</em> |

<hr />

### Class: \CourseHero\Theia\Client

> Class Client

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>\string</em> <strong>$endpoint</strong>, <em>[\CourseHero\Theia\CachingInterface](#interface-courseherotheiacachinginterface)</em> <strong>$cachingInterface=null</strong>, <em>array</em> <strong>$headers=array()</strong>)</strong> : <em>void</em><br /><em>Client constructor.</em> |
| public | <strong>cacheConfig()</strong> : <em>array</em><br /><em>Returns result of Theia server /cache-config</em> |
| public | <strong>config()</strong> : <em>array</em><br /><em>Returns result of Theia server /config</em> |
| public | <strong>render(</strong><em>\string</em> <strong>$componentLibrary</strong>, <em>\string</em> <strong>$component</strong>, <em>string/array</em> <strong>$props</strong>, <em>array</em> <strong>$queryParams=array()</strong>)</strong> : <em>[\CourseHero\Theia\RenderResult](#class-courseherotheiarenderresult)</em> |
| public | <strong>renderAndCache(</strong><em>\string</em> <strong>$componentLibrary</strong>, <em>\string</em> <strong>$component</strong>, <em>string/array</em> <strong>$props</strong>, <em>array</em> <strong>$queryParams=array()</strong>, <em>\boolean</em> <strong>$force=false</strong>, <em>\integer</em> <strong>$secondsUntilExpires=2592000</strong>)</strong> : <em>[\CourseHero\Theia\RenderResult](#class-courseherotheiarenderresult)</em> |

<hr />

### Class: \CourseHero\Theia\ReheatCache\JobCreator (abstract)

| Visibility | Function |
|:-----------|:---------|
| public | <strong>abstract createJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>mixed</em> |
| public | <strong>createProducerJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>mixed</em> |
| public | <strong>createRenderJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>mixed</em> |

<hr />

### Class: \CourseHero\Theia\ReheatCache\JobProcessor

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\CourseHero\Theia\ReheatCache\JobCreator](#class-courseherotheiareheatcachejobcreator-abstract)</em> <strong>$creator</strong>, <em>[\CourseHero\Theia\Client](#class-courseherotheiaclient)</em> <strong>$client</strong>)</strong> : <em>void</em> |
| public | <strong>getClient()</strong> : <em>mixed</em> |
| public | <strong>getJobCreator()</strong> : <em>mixed</em> |
| public | <strong>process(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>void</em> |
| public | <strong>registerJobHandler(</strong><em>[\CourseHero\Theia\ReheatCache\JobHandler](#class-courseherotheiareheatcachejobhandler-abstract)</em> <strong>$handler</strong>)</strong> : <em>void</em> |

<hr />

### Class: \CourseHero\Theia\ReheatCache\JobData

| Visibility | Function |
|:-----------|:---------|

<hr />

### Class: \CourseHero\Theia\ReheatCache\JobHandler (abstract)

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\CourseHero\Theia\ReheatCache\JobProcessor](#class-courseherotheiareheatcachejobprocessor)</em> <strong>$processor</strong>, <em>\string</em> <strong>$componentLibrary</strong>)</strong> : <em>void</em> |
| public | <strong>getComponentLibrary()</strong> : <em>mixed</em> |
| public | <strong>abstract processNewBuildJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>void</em> |
| public | <strong>abstract processProducerJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>void</em> |
| public | <strong>processRenderJob(</strong><em>[\CourseHero\Theia\ReheatCache\JobData](#class-courseherotheiareheatcachejobdata)</em> <strong>$data</strong>)</strong> : <em>void</em> |
| protected | <strong>createProducerJob(</strong><em>\string</em> <strong>$producerGroup</strong>, <em>array</em> <strong>$extra</strong>)</strong> : <em>mixed</em> |
| protected | <strong>createRenderJob(</strong><em>\string</em> <strong>$component</strong>, <em>\string</em> <strong>$props</strong>)</strong> : <em>mixed</em> |

