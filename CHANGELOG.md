## [0.4.3](https://github.com/craftile/php/compare/v0.4.2...v0.4.3) (2025-12-30)


### Bug Fixes

* **laravel:** properly escape file paths in compiled views for Windows ([5151abb](https://github.com/craftile/php/commit/5151abbf59f47c28f994590a064588e7f6304d66))



## [0.4.2](https://github.com/craftile/php/compare/v0.4.1...v0.4.2) (2025-12-11)


### Bug Fixes

* **laravel:** remove entire block subtree when deleting a block ([6bb1d9d](https://github.com/craftile/php/commit/6bb1d9d88c39f7186f4db80e9c62846ef6b70935))



## [0.4.1](https://github.com/craftile/php/compare/v0.4.0...v0.4.1) (2025-12-11)


### Bug Fixes

* update to Laravel 11, and symfony/yaml 7 ([4581f7e](https://github.com/craftile/php/commit/4581f7e5eec02376622e39a3800bfe64f99a3e89))



# [0.4.0](https://github.com/craftile/php/compare/v0.3.1...v0.4.0) (2025-12-10)


### Features

* **core:** add context() method to ContextAware trait ([61ab509](https://github.com/craftile/php/commit/61ab509e0637f8451d8370dd4fd068ffb9652cd8))
* **laravel:** add registerBlock and registerBlocks methods ([4304b27](https://github.com/craftile/php/commit/4304b27fc4a742a6e9eae3960dd8f6c9d796867a))



## [0.3.1](https://github.com/craftile/php/compare/v0.3.0...v0.3.1) (2025-10-27)


### Bug Fixes

* **laravel:** install missing symfony/yaml package ([c0cc057](https://github.com/craftile/php/commit/c0cc0571416d0623ba8add1a25d85ad45166ba88))



# [0.3.0](https://github.com/craftile/php/compare/v0.2.0...v0.3.0) (2025-10-24)


### Bug Fixes

* collect unrendered static/repeated blocks in preview data ([fa978aa](https://github.com/craftile/php/commit/fa978aa95dd12a8126294b916d82205bac906741))
* move view extension registration to booted callback for proper config timing ([ceec3a4](https://github.com/craftile/php/commit/ceec3a4dcab4515016ace268a8b6d35c3a72dc33))
* recursively collect entire tree of unrendered static/repeated blocks ([b9e9b39](https://github.com/craftile/php/commit/b9e9b391025c951474da25b1b85d230f489725cd))


### Features

* add region metadata support to Template fluent API ([f218b96](https://github.com/craftile/php/commit/f218b9698c8c6055e2518fd08f228df40bdb14bc))
* auto-generate IDs for blocks without explicit IDs in normalization pipeline ([0208da5](https://github.com/craftile/php/commit/0208da53d56eebd0fa4863ba258b1535004880ed))



# [0.2.0](https://github.com/craftile/php/compare/v0.1.0...v0.2.0) (2025-10-20)


### Bug Fixes

* **core:** Update BlockPreset and PresetChild to use static return types for inheritance ([82468b3](https://github.com/craftile/php/commit/82468b3097bef8581ed92951812220a41720f383))
* **laravel:** [@children](https://github.com/children) comment markers ([1ba6b0e](https://github.com/craftile/php/commit/1ba6b0eb40c1c20634658d02b1ad64306751d258))
* **laravel:** collect block data for editor even if the block is disabled ([6a1390a](https://github.com/craftile/php/commit/6a1390a4db15a80fbdcf5239f47fefc2984ffe35))
* **laravel:** fix [@craftile](https://github.com/craftile)Block and <craftile:block/> custom attributes compilation ([e3fb728](https://github.com/craftile/php/commit/e3fb7289471bb28c3f445038cbae60999c2ff173))
* **laravel:** improve HandleUpdates data normalization ([947ae45](https://github.com/craftile/php/commit/947ae45a5a7f1fc4060c06665aeea7076893d0a5))
* **laravel:** make sure ghost blocks are collected in preview mode for the editor ([b631da0](https://github.com/craftile/php/commit/b631da0b0be2d8a41d0cba4a8bddd513a7accbde))
* **laravel:** prevent parent block context from overriding child block context ([a6990d7](https://github.com/craftile/php/commit/a6990d74218eecaedc995913a2a7dce9860a4abf))
* **laravel:** remove childrenClosureCode from BlockCompilerInterface ([9ad6913](https://github.com/craftile/php/commit/9ad691310a734d3fd80bd14d041fbf74ea7c8dc2))
* **laravel:** skip updating blocks marked for removal and reindex filtered regions ([cce42c3](https://github.com/craftile/php/commit/cce42c3bd1ba8e6969a006e1dbedbec6238a41e1))
* **laravel:** when creating block data template context values that should override stored block data ([567b153](https://github.com/craftile/php/commit/567b15397fa0f65a7b97ee15de530e6ee426de37))
* region extraction in BlockFlatenner ([bd69c54](https://github.com/craftile/php/commit/bd69c5490919dec335b53a81a077405aa5e4ecdd))


### Features

* add block wrapper support ([3cbb7dd](https://github.com/craftile/php/commit/3cbb7dd5ccf4cbbe2019b8c73b0de05f8469bdd1))
* Add dynamic source properties value resolution for block properties ([c4f0055](https://github.com/craftile/php/commit/c4f0055f3c5ea8544969f5e3e8cd60204c0ac410))
* add previewImageUrl support to blocks and presets ([d6f4a03](https://github.com/craftile/php/commit/d6f4a03507a1cfefa7eed0ffc3fc160ad773bbae))
* add support for creating templates using PHP files (.craft.php) with a fluent API, providing a better developer experience than JSON/YAML ([42610b8](https://github.com/craftile/php/commit/42610b8a4f7ba9ffe9f7355c48ca4fff54d6e75d))
* add support for ghost/data-only blocks ([0bf0a28](https://github.com/craftile/php/commit/0bf0a28876ca0e20e6d03edd5d64b8da1a878831))
* add support for template normalizer ([1a0fba0](https://github.com/craftile/php/commit/1a0fba0f176bcc2600d1a763f7fdeeba03a1c165))
* add type/slug support and update BlockCompiler to use BlockSchema ([3592da1](https://github.com/craftile/php/commit/3592da1dc97750832b77d7efd1fb587cd664a93b))
* **core:** add asChild() API to BlockPreset so that it can be used as child in another preset ([e2a2f7c](https://github.com/craftile/php/commit/e2a2f7c88e25bcc5f085c5d63e96b28e1be0b939))
* **core:** Add conditional property field visibility with visibleIf ([44ebbca](https://github.com/craftile/php/commit/44ebbcad3fca35f8f778b2ec23ac77997574fd18))
* **core:** Add name property to BlockData ([d8494ec](https://github.com/craftile/php/commit/d8494ec9f664d4b9493c3b139b884b8368dc3d59))
* **core:** add private flag support to block schemas ([3e7668b](https://github.com/craftile/php/commit/3e7668b26cf1d45ffe687180ea75a4e304c5822f))
* **core:** add repeated() method to PresetChild ([b35ca55](https://github.com/craftile/php/commit/b35ca5504e22f1773973525d79a461ec51041005))
* **core:** Add responsive properties support ([0d3cb88](https://github.com/craftile/php/commit/0d3cb882976e8ddacc830c97253852c00d743b28))
* **core:** add support for custom block name to PresetChild ([77ef74c](https://github.com/craftile/php/commit/77ef74cb41779b6a1130f2afe1836b7f1c9cad13))
* **core:** Add support for custom BlockSchema class override ([36a7dd2](https://github.com/craftile/php/commit/36a7dd265cec5e514df3a5651bdea350edfd5d11))
* **core:** add support for reusable BlockPreset and PresetChild classes ([aeefba0](https://github.com/craftile/php/commit/aeefba057f378ec4da231e102dfe8a79e22bf395))
* **core:** support accessing default value via ->default in ResponsiveValue ([a73502d](https://github.com/craftile/php/commit/a73502d1d6e5d921eeee06ea343bbf6077d6f8b9))
* **core:** support block class names in accepts array ([288dc0a](https://github.com/craftile/php/commit/288dc0a3993d6a67f46b09ba1563aab4b91914e9))
* **laravel:** add block index/iteration support ([ad55ba0](https://github.com/craftile/php/commit/ad55ba0580ba898fd1f809672861a9f053b0586d))
* **laravel:** add block presets support ([9cc3192](https://github.com/craftile/php/commit/9cc31921ff7637fd7440c1985805c17aed85b496))
* **laravel:** add cache invalidation for nested blocks in JSON view compilation ([715c1b1](https://github.com/craftile/php/commit/715c1b1a03ce93ed2f28ba61b9de98b6d358f55a))
* **laravel:** add comment markers around [@children](https://github.com/children) compilation ([7973330](https://github.com/craftile/php/commit/79733307c7cfaded19ec8a6b518862c44d57cc7f))
* **laravel:** add configuration for region view resolver ([1445f45](https://github.com/craftile/php/commit/1445f45e251d826eafc1b08b5caccc398221f2a8))
* **laravel:** add HandleUpdates and UpdateRequest DTO for editor updates ([d51b657](https://github.com/craftile/php/commit/d51b657186c215dd3c24d0653c57a8afabe5999b))
* **laravel:** add parent id and semantic id tracking to static blocks ([69e72f1](https://github.com/craftile/php/commit/69e72f1cc9321491975527bbcebfea3980f1fb6d))
* **laravel:** add source file tracking to BlockData instances ([0f2ea7d](https://github.com/craftile/php/commit/0f2ea7d40e3b482b62b99761be8bc951a834095d))
* **laravel:** add support for custom BlockData ([bfec696](https://github.com/craftile/php/commit/bfec6967d700e0048b8c471314cc932d2fadfb6f))
* **laravel:** dispatch JsonViewLoaded when a json view is rendered ([b119dfe](https://github.com/craftile/php/commit/b119dfe0ba1e928e7198963c9e4777e19b59b7b0))
* **laravel:** dispatch JsonViewLoaded when a json view is rendered ([ce9de81](https://github.com/craftile/php/commit/ce9de811806052cd265ab096a0ae604e62b52f2b))
* **laravel:** improved json view compiler to compile blocks children to separate file ([eb69b08](https://github.com/craftile/php/commit/eb69b08655f8fa3d4ac56a09f3df1d1cc5f57588))
* **laravel:** improved json view compiler to compile blocks children to separate file ([f4f17c4](https://github.com/craftile/php/commit/f4f17c45288331c11f4955544584948a3ec5ff4b))
* **laravel:** propagate block context to children and add support for share() to share data with children ([1c56f8c](https://github.com/craftile/php/commit/1c56f8cfb25f2c71b28dc72af3485a89a1dc8f31))
* **laravel:** re-add source file tracking to BlockData instances ([7c9a32d](https://github.com/craftile/php/commit/7c9a32dcf4672a18ea7a5046e1624959aed21619))
* **laravel:** track visual order of static and dynamic blocks in preview ([28e4681](https://github.com/craftile/php/commit/28e468149abac8cb5c5be5daebfd024cd3655a1e))
* **laravel:** update json view compiler to not render disabled blocks ([1350263](https://github.com/craftile/php/commit/135026342527f06242f3b8f5e57ac2855dc02d0c))



# [0.1.0](https://github.com/craftile/php/compare/69e93be5b9483e8b941b3819301d316c11b741f8...v0.1.0) (2025-09-25)


### Bug Fixes

* add missing core tests ([e64955d](https://github.com/craftile/php/commit/e64955d9ed7970ccdbe2444ad84e9fe7a3506590))
* **ci:** update release workflow to manual dispatch only ([161270f](https://github.com/craftile/php/commit/161270fa667823f6e874850a5ffcd004bc6213a0))
* tests namespace issue ([c6fca36](https://github.com/craftile/php/commit/c6fca369aa8d0f1b56b806163db7e94f86606cba))


### Features

* add base Property class ([74c791d](https://github.com/craftile/php/commit/74c791d36021ad568ffa96a20e9831c1cb480302))
* add BladeComponentBlockCompiler for Laravel component integration ([c3f3c2b](https://github.com/craftile/php/commit/c3f3c2b97b2d716fe8c7df11d5413b57255ab24e))
* add block flattener ([f2da413](https://github.com/craftile/php/commit/f2da413311ef3aebaf0cbdb1963d193342213afb))
* add core block system ([6f9aa46](https://github.com/craftile/php/commit/6f9aa464a40ef415d9f45e16ab45bb6f425c89f4))
* custom laravel BlockData ([c345643](https://github.com/craftile/php/commit/c345643a44c9727b3c9d2ee12ada42d9e5f98bf9))
* implemented json view compilation ([b17e63c](https://github.com/craftile/php/commit/b17e63ce57a5964b3dd8b82dcbaa94b0887119ce))
* initial commit ([69e93be](https://github.com/craftile/php/commit/69e93be5b9483e8b941b3819301d316c11b741f8))
* **laravek:** add block discovery ([789ed05](https://github.com/craftile/php/commit/789ed050c181b09b150b8a56b0dda376124cfffb))
* **laravel:** add block auto-discovery configuration ([4507354](https://github.com/craftile/php/commit/4507354f6bfac2d587dac31212151e6e61cafb23))
* **laravel:** add config publishing ([510a560](https://github.com/craftile/php/commit/510a560514cbacc132bc91c92bfae5c881d21ddb))
* **laravel:** add property transformer system ([5d1e42b](https://github.com/craftile/php/commit/5d1e42bbb501d2c7ef75bf05adf2b764e2bb8e9b))
* **laravel:** blocks rendering system ([069cb22](https://github.com/craftile/php/commit/069cb224035d98ac52bd611ec47302b2bfd8602a))
* update split workflow ([56a44ed](https://github.com/craftile/php/commit/56a44ed62d3be70991de52c77b5175a3cfdc40b5))
* update workflows to PHP 8.2 ([dddc614](https://github.com/craftile/php/commit/dddc614f440574ae99a3f85d283b3b8d12100810))



