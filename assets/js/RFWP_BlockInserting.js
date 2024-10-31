window.RFWP_BlockInserting = class {
    _blockSettingArray;

    static endedSc = false;
    static endedCc = false;

    constructor(blockSettingArray) {
        this._blockSettingArray = blockSettingArray;
    }

    static launch(blockSettingArray) {
        var $this = this;
        if (window.jsInputerLaunch !== undefined && [15, 10].includes(window.jsInputerLaunch)) {
            var item = new RFWP_BlockInserting(blockSettingArray);
            item.insert();
            if (!this.endedSc) {
                item.shortcodesInsert();
            }
            if (!this.endedCc) {
                /* clearUnsuitableCache(0); */
            }
            /* blocksReposition();
            cachePlacing();
            symbolMarkersPlaced(); */
        } else {
            setTimeout(function () {
                $this.launch(blockSettingArray);
            }, 50);
        }
    }

    insert() {
        let repeatInsert = false;
        let index, parent_with_content, usedBlockSettingArrayIds, lordOfElements, contentLength, rejectedBlocks,
            containerFor6th, containerFor7th, countHeaders, blockSetting, currentElement, currentElementArray, elPlace;

        document.querySelectorAll(".content_pointer_class").forEach((content_pointer) => {
            parent_with_content = content_pointer.parentElement;
            usedBlockSettingArrayIds = (content_pointer.getAttribute('data-block-ids') || "").split(',');
            lordOfElements = parent_with_content;
            parent_with_content = parent_with_content.parentElement;
            contentLength = content_pointer.getAttribute('data-content-length');
            rejectedBlocks = content_pointer.getAttribute('data-rejected-blocks');
            if (rejectedBlocks&&rejectedBlocks.length > 0) {
                rejectedBlocks = rejectedBlocks.split(',');
            }
            containerFor6th = [];
            containerFor7th = [];

            countHeaders = parent_with_content.querySelectorAll('h1, h2, h3, h4, h5, h6').length;

            while((index = usedBlockSettingArrayIds.findIndex((el) => el === "")) >= 0) {
                usedBlockSettingArrayIds.splice(index, 1);
            }

            if (contentLength < 1) {
                contentLength = parent_with_content.innerText.length
            }

            for (var i = 0; i < this._blockSettingArray.length; i++) {
                blockSetting = this._blockSettingArray[i];
                currentElement = null;
                currentElementArray = [];

                try {
                    const binderName = blockSetting["rb_under"];

                    if (!blockSetting["text"] ||
                        (blockSetting["text"] && blockSetting["text"].length < 1)
                    ) {
                        this._blockSettingArray.splice(i--, 1);
                        continue;
                    }

                    if (rejectedBlocks&&rejectedBlocks.includes(blockSetting["id"]) ||
                        (blockSetting["maxHeaders"] > 0 && blockSetting["maxHeaders"] < parseInt(countHeaders)) ||
                        (blockSetting["maxSymbols"] > 0 && blockSetting["maxSymbols"] < parseInt(contentLength)) ||
                        (content_pointer.classList.contains("hard-content") && blockSetting["setting_type"] !== 3)
                    ) {
                        continue;
                    }

                    const elementToAdd = this.createBlockElement(blockSetting),
                        block_number = elementToAdd.children[0].attributes['data-id'].value;

                    if (usedBlockSettingArrayIds.length > 0 && usedBlockSettingArrayIds.includes(block_number)) {
                        continue;
                    }

                    if ((blockSetting["minHeaders"] > 0)&&(blockSetting["minHeaders"] > countHeaders)) {continue;}
                    if (blockSetting["minSymbols"] > contentLength) {continue;}

                    if (blockSetting["setting_type"] == 1) {
                        currentElementArray = this.currentElementsGather(blockSetting["element"].toLowerCase(), parent_with_content);
                        if (!!currentElementArray && currentElementArray.length > 0) {
                            if (blockSetting["elementPlace"] < 0) {
                                elPlace = currentElementArray.length + blockSetting["elementPlace"];
                                if (elPlace >= 0 && elPlace < currentElementArray.length) {
                                    currentElement = this.currentElementReceiver(true, content_pointer, blockSetting, currentElementArray, elPlace);
                                }
                            } else {
                                elPlace = blockSetting["elementPlace"] - 1;
                                if (elPlace < currentElementArray.length) {
                                    currentElement = this.currentElementReceiver(false, content_pointer, blockSetting, currentElementArray, elPlace);
                                }
                            }
                        }
                        if (!currentElement && blockSetting["showNoElement"]) {
                            currentElement = currentElementArray[currentElementArray.length - 1];
                        }
                        if (!!currentElement) {
                            this.addBlockAd(blockSetting, currentElement, elementToAdd);
                            usedBlockSettingArrayIds.push(block_number);
                        } else {
                            repeatInsert = true;
                        }
                    }
                    else if (blockSetting["setting_type"] == 2) {
                        if (blockDuplicate == 'no') {
                            blockSetting["elementCount"] = 1;
                        }
                        var curFirstPlace = blockSetting["firstPlace"],
                            curElementCount = blockSetting["elementCount"],
                            curElementStep = blockSetting["elementStep"],
                            repeatableBlockIdentifier = 0,
                            successAdd = false;

                        currentElementArray = this.currentElementsGather(blockSetting["element"].toLowerCase(), parent_with_content);
                        if (currentElementArray) {
                            for (let i1 = 0; i1 < blockSetting["elementCount"]; i1++) {
                                elementToAdd.classList.add("repeatable-mark-" + repeatableBlockIdentifier);

                                elPlace = Math.round(parseInt(blockSetting["firstPlace"]) + (i1*parseInt(blockSetting["elementStep"])) - 1);
                                if (elPlace < currentElementArray.length) {
                                    currentElement = this.currentElementReceiver(false, content_pointer, blockSetting, currentElementArray, elPlace);
                                }
                                if (!currentElement && blockSetting["showNoElement"] && !i1) {
                                    currentElement = currentElementArray[currentElementArray.length - 1];
                                }

                                if (currentElement !== undefined && currentElement != null) {
                                    this.addBlockAd(blockSetting, currentElement, elementToAdd);
                                    curFirstPlace = elPlace + parseInt(blockSetting["elementStep"]) + 1;
                                    curElementCount--;
                                    successAdd = true;
                                } else {
                                    successAdd = false;
                                    break;
                                }
                            }
                        }
                        if (successAdd === true) {
                            usedBlockSettingArrayIds.push(block_number);
                            repeatableBlockIdentifier++;
                        } else {
                            if (!blockSetting["unsuccess"]) {
                                blockSetting["unsuccess"] = 1;
                            } else {
                                blockSetting["unsuccess"] = Math.round(blockSetting["unsuccess"] + 1);
                            }
                            if (blockSetting["unsuccess"] > 10) {
                                usedBlockSettingArrayIds.push(block_number);
                            } else {
                                blockSetting["firstPlace"] = curFirstPlace;
                                blockSetting["elementCount"] = curElementCount;
                                blockSetting["elementStep"] = curElementStep;
                                repeatInsert = true;
                            }
                        }
                    }
                    else if (blockSetting["setting_type"] == 3) {
                        currentElement = this.getElementBySelection(blockSetting["directElement"].trim(), blockSetting)

                        if (!!currentElement) {
                            this.addBlockAd(blockSetting, currentElement, elementToAdd);
                            usedBlockSettingArrayIds.push(block_number);
                            this._blockSettingArray.splice(i--, 1);
                        } else {
                            repeatInsert = true;
                        }
                    }
                    else if (blockSetting["setting_type"] == 4) {
                        content_pointer.parentElement.append(elementToAdd);
                        usedBlockSettingArrayIds.push(block_number);
                    }
                    else if (blockSetting["setting_type"] == 5) {
                        currentElementArray = this.currentElementsGather('p', content_pointer.parentElement, 1);
                        if (currentElementArray && currentElementArray.length > 0) {
                            let pCount = currentElementArray.length;
                            let elementNumber = Math.round(pCount/2);
                            if (pCount > 1) {
                                currentElement = currentElementArray[elementNumber+1];
                            }
                            if (!!currentElement) {
                                if (pCount > 1) {
                                    this.addBlockAd(blockSetting, currentElement, elementToAdd, currentElement);
                                } else {
                                    this.addBlockAd(blockSetting, currentElement, elementToAdd, currentElement.nextSibling);
                                }
                                usedBlockSettingArrayIds.push(block_number);
                            } else {
                                repeatInsert = true;
                            }
                        } else {
                            repeatInsert = true;
                        }
                    }
                    else if (blockSetting["setting_type"] == 6) {
                        if (containerFor6th.length > 0) {
                            for (let j = 0; j < containerFor6th.length; j++) {
                                if (containerFor6th[j]["elementPlace"]>blockSetting["elementPlace"]) {
                                    /* continue; */
                                    if (j === containerFor6th.length-1) {
                                        containerFor6th.push(blockSetting);
                                        usedBlockSettingArrayIds.push(block_number);
                                        break;
                                    }
                                } else {
                                    containerFor6th.splice(j, 0, blockSetting)
                                    usedBlockSettingArrayIds.push(block_number);
                                    break;
                                }
                            }
                        } else {
                            containerFor6th.push(blockSetting);
                            usedBlockSettingArrayIds.push(block_number);
                        }
                        /* vidpravutu v vidstiinuk dlya 6ho tipa */
                    }
                    else if (blockSetting["setting_type"] == 7) {
                        if (containerFor7th.length > 0) {
                            for (let j = 0; j < containerFor7th.length; j++) {
                                if (containerFor7th[j]["elementPlace"]>blockSetting["elementPlace"]) {
                                    /* continue; */
                                    if (j == containerFor7th.length-1) {
                                        containerFor7th.push(blockSetting);
                                        usedBlockSettingArrayIds.push(block_number);
                                        break;
                                    }
                                } else {
                                    containerFor7th.splice(j, 0, blockSetting)
                                    usedBlockSettingArrayIds.push(block_number);
                                    break;
                                }
                            }
                        } else {
                            containerFor7th.push(blockSetting);
                            usedBlockSettingArrayIds.push(block_number);
                        }
                    }
                } catch (e) {
                    console.log(e.message);
                }
            }

            var array = this.textLengthGatherer(lordOfElements),
                tlArray = array.array,
                length = array.length;

            if (containerFor6th.length > 0) {
                this.percentInserter(lordOfElements, containerFor6th, tlArray, length);
            }
            if (containerFor7th.length > 0) {
                this.symbolInserter(lordOfElements, containerFor7th, tlArray);
            }
            this.shortcodesInsert();
            content_pointer.setAttribute("data-block-ids", usedBlockSettingArrayIds.join(","))
        });

        let stopper = 0,
            $this = this;

        window.addEventListener('load', function () {
            if (repeatInsert === true) {
                setTimeout(function () {
                    $this.insert();
                }, 100);
            }
        });
    }

    createBlockElement(blockSetting) {
        let element = document.createElement("div");

        element.classList.add("percentPointerClass");
        element.classList.add("marked");
        if (blockSetting["sc"] === 1) {
            element.classList.add("scMark");
        }
        element.innerHTML = blockSetting["text"];
        element.dataset.rbinder = blockSetting["rb_under"];

        const block_number = element.children[0].attributes['data-id'].value,
            elementToAddStyle = this.createStyleElement(block_number, blockSetting["elementCss"]);

        if (elementToAddStyle&&elementToAddStyle!=='default') {
            element.style.textAlign = elementToAddStyle;
        }

        return element
    }

    addBlockAd(blockSetting, currentElement, elementToAdd, position = null) {
        if (!position) {
            position = this.initTargetToInsert(blockSetting["elementPosition"], 'element', currentElement);
        }
        currentElement.parentNode.insertBefore(elementToAdd, position);
        currentElement.classList.add('rbinder-'+blockSetting["rb_under"]);
        elementToAdd.classList.remove('coveredAd');
    }

    getElementBySelection(directElement, blockSetting) {
        if (directElement.search('#') > -1) {
            return document.querySelector(directElement);
        }
        if ((directElement.search('#') < 0)&&(directElement.search('.') > -1)) {
            return this.directClassElementDetecting(directElement, blockSetting);
        }
    }

    directClassElementDetecting(directElement, blockSetting) {
        let findQuery = false;
        let currentElementArray = document.querySelectorAll(directElement);
        let currentElement = null;

        if (currentElementArray.length > 0) {
            if (blockSetting['elementPlace'] > 1) {
                if (currentElementArray.length >= blockSetting['elementPlace']) {
                    currentElement = currentElementArray[blockSetting['elementPlace']-1];
                } else if (currentElementArray.length < blockSetting['elementPlace']) {
                    if (blockSetting['showNoElement'] > 0) {
                        currentElement = currentElementArray[currentElementArray.length - 1];
                    }
                } else {
                    findQuery = true;
                }
            } else if (blockSetting['elementPlace'] < 0) {
                if ((currentElementArray.length + blockSetting['elementPlace'] + 1) > 0) {
                    currentElement = currentElementArray[currentElementArray.length + blockSetting['elementPlace']];
                } else {
                    findQuery = true;
                }
            } else {
                findQuery = true;
            }
        } else {
            findQuery = true;
        }

        if (findQuery) {
            currentElement = document.querySelector(directElement);
        }

        return currentElement;
    }

    placingArrayToH1(usedElement, elementTagToFind) {
        let elements = usedElement.querySelectorAll(elementTagToFind);

        if (elements.length < 1) {
            if (usedElement.parentElement) {
                elements = this.placingArrayToH1(usedElement.parentElement, elementTagToFind);
            }
        }
        return elements;
    }

    elementsCleaning(excArr, elList, pwcLocal, gatherString) {
        let markedClass = 'rb_m_inc';
        let markedClassBad = 'rb_m_exc';
        let cou = 0;
        let cou1 = 0;
        let finalArr = [];
        let finalArrClear = [];
        let checkNearest;
        let outOfRangeCheck;
        let gatherRejected;
        let allower;

        try {
            while (elList[cou]) {
                allower = true;
                if (!elList[cou].classList.contains(markedClassBad)) {
                    if (excArr&&excArr.length > 0) {
                        cou1 = 0;
                        while (excArr[cou1]) {
                            checkNearest = elList[cou].parentElement.closest(excArr[cou1]);
                            if (checkNearest) {
                                checkNearest.classList.add('currClosest');
                                outOfRangeCheck = pwcLocal.querySelector('.currClosest');
                                if (outOfRangeCheck) {
                                    allower = false;
                                    checkNearest.classList.add(markedClass);
                                    gatherRejected = checkNearest.querySelectorAll(gatherString);
                                    if (gatherRejected.length > 0) {
                                        for (let i1 = 0; i1 < gatherRejected.length; i1++) {
                                            gatherRejected[i1].classList.add(markedClassBad);
                                        }
                                    }
                                }
                                checkNearest.classList.remove('currClosest');
                            }
                            cou1++;
                        }
                    }
                    if (allower===true) {
                        elList[cou].classList.add(markedClass);
                        /* finalArr.push(elList[cou]); */
                    }
                }
                cou++;
            }
            finalArr = pwcLocal.querySelectorAll('.'+markedClass+':not('+markedClassBad+')');
            finalArrClear = pwcLocal.querySelectorAll('.'+markedClass+',.'+markedClassBad);
            if (finalArrClear&&finalArrClear.length > 0) {
                for (let i1 = 0; i1 < finalArrClear.length; i1++) {
                    finalArrClear[i1].classList.remove(markedClass,markedClassBad);
                }
            }
        } catch (er) {
            console.log(er.message);
        }
        return finalArr;
    }

    currentElementsGather(usedElement, localPwc, loopLimit = 2, ) {
        let curElementSearchRepeater = true;
        let curElementSearchCounter = 0;
        let currentElementArray = null;
        let ExcludedString = '';
        let tagListString = '';
        let tagListStringExc = '';
        let cou = 0;
        let tagList;
        /* let excArr = excIdClUnpacker(); */
        let tagListCou = 0;

        if (usedElement==='h1') {
            currentElementArray = this.placingArrayToH1(localPwc, usedElement);
        } else {
            if (usedElement==='h2-4')
                tagList = ['h2','h3','h3'];
            else
                tagList = [usedElement];

            while (tagList[tagListCou]) {
                tagListString += ((cou++ > 0) ? ',' : '') + tagList[tagListCou];
                tagListStringExc += ':not(' + tagList[tagListCou] + ')';
                tagListCou++;
            }

            ExcludedString = '';
            if (excIdClass&&excIdClass.length > 0) {
                for (let i2 = 0; i2 < excIdClass.length; i2++) {
                    if (excIdClass[i2].length > 0) {
                        ExcludedString += (i2>0?',':'')+excIdClass[i2]+tagListStringExc;
                    }
                }
            }
            let detailedQueryString = tagListString+','+ExcludedString;

            /* console.log(detailedQueryString); */
            while (curElementSearchRepeater&&curElementSearchCounter < loopLimit) {
                try {
                    currentElementArray = localPwc.querySelectorAll(tagListString);
                } catch (e1) {console.log(e1.message);}
                if (!currentElementArray || !currentElementArray.length) {
                    if (localPwc.parentElement) {
                        localPwc = localPwc.parentElement;
                    } else {
                        break;
                    }
                } else {
                    currentElementArray = this.elementsCleaning(excIdClass, currentElementArray, localPwc, detailedQueryString);
                    curElementSearchRepeater = false;
                }
                curElementSearchCounter++;
            }
        }
        return currentElementArray;
    }

    currentElementReceiver(revert, content_pointer, blockSetting, currentElementArray, elPlace) {
        let currentElement = null;
        let sameElementAfterWidth = false;
        let testCou = 0;
        while (currentElementArray[elPlace] && sameElementAfterWidth === false && testCou < 8) {
            currentElement = currentElementArray[elPlace];
            try {
                sameElementAfterWidth = this.checkAdsWidth(content_pointer, blockSetting["elementPosition"], currentElement);
            } catch (ex) {
                sameElementAfterWidth = true;
                console.log(ex.message);
            }
            revert? elPlace--: elPlace++;
            testCou++;
        }

        return currentElement;
    }


    symbolInserter(lordOfElements, containerFor7th, tlArray) {
        try {
            var currentChildrenLength = 0;
            let previousBreak = 0;
            let needleLength;
            let currentSumLength;
            let elementToAdd;
            let elementToBind;
            let binderName;

            if (!lordOfElements.querySelector(".markedSpan1")) {
                for (let i = 0; i < containerFor7th.length; i++) {
                    previousBreak = 0;
                    currentChildrenLength = 0;
                    currentSumLength = 0;
                    needleLength = Math.abs(containerFor7th[i]['elementPlace']);
                    binderName = containerFor7th[i]["rb_under"];

                    elementToAdd = this.createBlockElement(containerFor7th[i]);
                    if (!elementToAdd) {
                        continue;
                    }

                    if (containerFor7th[i]['elementPlace'] < 0) {
                        for (let j = tlArray.length-1; j > -1; j--) {
                            currentSumLength = currentSumLength + tlArray[j]['length'];
                            if (needleLength < currentSumLength) {
                                elementToBind = tlArray[j]['element'];
                                elementToBind = this.currentElementReceiverSpec(true, j, tlArray, elementToBind);
                                this.addBlockAd(containerFor7th[i], elementToBind, elementToAdd, elementToBind);
                                break;
                            }
                        }
                    } else if (containerFor7th[i]['elementPlace'] == 0) {
                        elementToBind = tlArray[0]['element'];
                        this.addBlockAd(containerFor7th[i], elementToBind, elementToAdd, elementToBind);
                    } else {
                        for (let j = 0; j < tlArray.length; j++) {
                            currentSumLength = currentSumLength + tlArray[j]['length'];
                            if (needleLength < currentSumLength) {
                                elementToBind = tlArray[j]['element'];
                                elementToBind = this.currentElementReceiverSpec(false, j, tlArray, elementToBind);
                                this.addBlockAd(containerFor7th[i], elementToBind, elementToAdd, elementToBind.nextSibling);
                                break;
                            }
                        }
                    }
                }

                var spanMarker = document.createElement("span");
                spanMarker.classList.add("markedSpan1");
                lordOfElements.prepend(spanMarker);
            }
        } catch (e) {
            console.log(e);
        }
    }

    percentInserter(lordOfElements, containerFor6th, tlArray, textLength) {
        try {
            var textNeedyLength = 0;
            let elementToAdd;
            var elementToBind;
            let elementToAddStyle;
            let block_number;
            var binderName;
            let $this = this;

            function insertByPercents(textLength) {
                let localMiddleValue = 0;

                for (let j = 0; j < containerFor6th.length; j++) {
                    textNeedyLength = Math.round(textLength * (containerFor6th[j]["elementPlace"]/100));
                    for (let i = 0; i < tlArray.length; i++) {
                        if (tlArray[i]['lengthSum'] >= textNeedyLength) {
                            binderName = containerFor6th[j]["rb_under"];
                            elementToAdd = $this.createBlockElement(containerFor6th[j]);
                            if (!elementToAdd) {
                                break;
                            }

                            localMiddleValue = tlArray[i]['lengthSum'] - Math.round(tlArray[i]['length']/2);
                            elementToBind = tlArray[i]['element'];
                            $this.currentElementReceiverSpec(false, i, tlArray, elementToBind);
                            if (textNeedyLength < localMiddleValue) {
                                $this.addBlockAd(containerFor6th[j], elementToBind, elementToAdd, elementToBind);
                            } else {
                                $this.addBlockAd(containerFor6th[j], elementToBind, elementToAdd, elementToBind.nextSibling);
                            }
                            break;
                        }
                    }
                }
                return false;
            }

            function clearTlMarks() {
                let marksForDeleting = document.querySelectorAll('.textLengthMarker');

                if (marksForDeleting.length > 0) {
                    for (let i = 0; i < marksForDeleting.length; i++) {
                        marksForDeleting[i].remove();
                    }
                }
            }

            if (!lordOfElements.querySelector(".markedSpan")) {
                insertByPercents(textLength);
                clearTlMarks();
                var spanMarker = document.createElement("span");
                spanMarker.classList.add("markedSpan");
                lordOfElements.prepend(spanMarker);
            }
        } catch (e) {
            console.log(e.message);
        }
    }


    /* "sc" in variables - mark for shortcode variable */
    shortcodesInsert() {
        let gatheredBlocks = document.querySelectorAll('.percentPointerClass.scMark'),
            scBlockId = -1,
            scAdId = -1,
            blockStatus = '',
            dataFull = -1,
            gatheredBlockChild,
            okStates = ['done','refresh-wait','no-block','fetched'],
            scContainer,
            sci,
            i1 = 0,
            skyscraperStatus = false,
            splitedSkyscraper = [],
            gatheredBlockChildSkyParts = [],
            stickyStatus = false,
            stickyCheck = [],
            stickyFixedStatus = false,
            stickyFixedCheck = [],
            repeatableIdentifier = "",
            dataCidIdentifier = null,
            divCidElement = '';

        if (typeof scArray !== 'undefined') {
            if (scArray&&scArray.length > 0&&gatheredBlocks&&gatheredBlocks.length > 0&&typeof window.rulvW5gntb !== 'undefined') {
                dataCidIdentifier = window.rulvW5gntb;
                for (let i = 0; i < gatheredBlocks.length; i++) {
                    gatheredBlockChild = gatheredBlocks[i].children[0];
                    if (!gatheredBlockChild) {
                        continue;
                    }
                    scAdId = -3;
                    blockStatus = null;
                    scContainer = null;
                    dataFull = -1;
                    skyscraperStatus = false;
                    splitedSkyscraper = [];
                    gatheredBlockChildSkyParts = [];
                    stickyStatus = false;
                    stickyCheck = [];
                    stickyFixedStatus = false;
                    stickyFixedCheck = [];
                    repeatableIdentifier = "";
                    divCidElement = null;

                    scAdId = gatheredBlockChild.getAttribute('data-aid');
                    scBlockId = gatheredBlockChild.getAttribute('data-id');
                    blockStatus = gatheredBlockChild.getAttribute('data-state');
                    dataFull = gatheredBlockChild.getAttribute('data-full');

                    if (scBlockId&&scAdId > 0) {
                        sci = -1;
                        for (i1 = 0; i1 < scArray.length; i1++) {
                            if (scBlockId == scArray[i1]['blockId']&&scAdId == scArray[i1]['adId']) {
                                sci = i1;
                            }
                        }

                        if (sci > -1) {
                            if (blockStatus&&okStates.includes(blockStatus)) {

                                if (blockStatus=='no-block') {
                                    gatheredBlockChild.innerHTML = '';
                                } else if ((blockStatus=='fetched'&&dataFull==1)||!['no-block','fetched'].includes(blockStatus)) {
                                    for (let cl1 = 0; cl1 < gatheredBlocks[i].classList.length; cl1++) {
                                        if (gatheredBlocks[i].classList[cl1].includes("repeatable-mark")) {
                                            repeatableIdentifier = gatheredBlocks[i].classList[cl1];
                                        }
                                    }

                                    if (repeatableIdentifier) {
                                        divCidElement = document.querySelectorAll(".percentPointerClass.scMark."+repeatableIdentifier+' div[data-cid="'+dataCidIdentifier+'"]');
                                    } else {
                                        divCidElement = gatheredBlockChild.querySelectorAll('div[data-cid="'+dataCidIdentifier+'"]');
                                    }

                                    var text = scArray[sci]['text'];
                                    if (divCidElement&&divCidElement.length > 0) {
                                        for (let i2 = 0; i2 < divCidElement.length; i2++) {
                                            jQuery(divCidElement[i2]).html(text);
                                        }
                                    } else {
                                        jQuery(gatheredBlockChild).html(text);
                                    }
                                    this.launchUpdateRbDisplays();
                                }
                                if (blockStatus !== 'fetched' || (blockStatus === 'fetched' && dataFull === 1)) {
                                    gatheredBlocks[i].classList.remove('scMark');
                                }
                            }
                        }
                    } else if (scBlockId&&scAdId < 1&&['no-block','fetched'].includes(blockStatus)) {
                        gatheredBlocks[i].classList.remove('scMark');
                    }
                }
            } else if (!scArray||(scArray&&scArray.length < 1)) {
                this.endedSc = true;
            }
        } else {
            this.endedSc = true;
        }

        if (!this.endedSc) {
            var $this = this;
            setTimeout(function () {
                $this.shortcodesInsert();
            }, 200);
        }
    }

    currentElementReceiverSpec(revert, curSum, elList, currentElement) {
        let origCurrentElement = currentElement;
        let content_pointer = document.querySelector(".content_pointer_class"); /* orig */
        let sameElementAfterWidth = false;
        let testCou = 0;
        while (elList[curSum] && !sameElementAfterWidth && testCou < 5) {
            currentElement = elList[curSum]['element'];
            try {
                sameElementAfterWidth = this.checkAdsWidth(content_pointer, 0, currentElement);
            } catch (ex) {
                sameElementAfterWidth = true;
                console.log(ex.message);
            }
            revert? curSum--: curSum++;
            testCou++;
        }
        return currentElement?currentElement:origCurrentElement;
    }

    launchUpdateRbDisplays() {
        if ((typeof updateRbDisplays !== 'undefined')&&(typeof updateRbDisplays === 'function')) {
            updateRbDisplays();
        } else {
            setTimeout(function () {
                this.launchUpdateRbDisplays();
            }, 200);
        }
    }

    checkAdsWidth(content_pointer, posCurrentElement, currentElement) {
        let widthChecker = document.querySelector('#widthChecker');
        let widthCheckerStyle = null;
        let content_pointerStyle = getComputedStyle(content_pointer);
        let content = content_pointer.parentElement;

        if (!widthChecker) {
            widthChecker = document.createElement("div");
            widthChecker.setAttribute('id','widthChecker');
            widthChecker.style.display = 'flex';
        }

        if (content) {
            posCurrentElement = this.initTargetToInsert(posCurrentElement, 'term', currentElement);
            currentElement.parentNode.insertBefore(widthChecker, posCurrentElement);
            widthCheckerStyle = getComputedStyle(widthChecker);

            if (parseInt(widthCheckerStyle.width) >= (parseInt(content_pointerStyle.width) - 50)) {
                return true;
            }
        }
        return false;
    }


    initTargetToInsert(position, type, currentElement) {
        let posCurrentElement;
        let usedElement;
        if (type == 'element') {
            if (position == 0) {
                posCurrentElement = currentElement;
                if (!(typeof obligatoryMargin!=='undefined'&&obligatoryMargin===1)) {
                    currentElement.classList.add('rfwp_removedMarginTop');
                }
            } else {
                posCurrentElement = currentElement.nextSibling;
                if (!(typeof obligatoryMargin!=='undefined'&&obligatoryMargin===1)) {
                    currentElement.classList.add('rfwp_removedMarginBottom');
                }
            }
            currentElement.style.clear = 'both';
        } else {
            usedElement = currentElement;
            if (position == 0) {
                posCurrentElement = usedElement;
            } else {
                posCurrentElement = usedElement.nextSibling;
            }
        }
        return posCurrentElement;
    }

    createStyleElement(blockNumber, localElementCss) {
        let htmlToAdd = '';
        let marginString;
        let textAlignString;
        let contPois = document.querySelector('.content_pointer_class');
        let emptyValues = false;
        let elementToAddStyleLocal;

        if (!contPois.length)
            return false;

        contPois.forEach((contPoi) => {
            elementToAddStyleLocal = contPoi.querySelector('.blocks_align_style');

            if (!elementToAddStyleLocal) {
                elementToAddStyleLocal = document.createElement('style');
                elementToAddStyleLocal.classList.add('blocks_align_style');
                contPoi.parentNode.insertBefore(elementToAddStyleLocal, contPoi);
            }
        });



        switch (localElementCss) {
            case 'left':
                emptyValues = false;
                marginString = '0 auto 0 0';
                textAlignString = 'left';
                break;
            case 'right':
                emptyValues = false;
                marginString = '0 0 0 auto';
                textAlignString = 'right';
                break;
            case 'center':
                emptyValues = false;
                marginString = '0 auto';
                textAlignString = 'center';
                break;
            case 'default':
                emptyValues = true;
                marginString = 'default';
                textAlignString = 'default';
                break;
        }
        if (!emptyValues) {
            htmlToAdd = '.percentPointerClass  > *[data-id="'+blockNumber+'"] {\n' +
                '    margin: '+marginString+';\n' +
                '}\n';
        }

        elementToAddStyleLocal.innerHTML += htmlToAdd;
        return textAlignString;
    }

    clearUnsuitableCache(cuc_cou) {
        let scAdId = -1;
        let ccRepeat = false;

        let gatheredBlocks = document.querySelectorAll('.percentPointerClass .' + block_classes.join(', .percentPointerClass .'));

        if (gatheredBlocks&&gatheredBlocks.length > 0) {
            for (let i = 0; i < gatheredBlocks.length; i++) {
                if (gatheredBlocks[i]['dataset']['aid']&&gatheredBlocks[i]['dataset']['aid'] < 0) {
                    if ((gatheredBlocks[i]['dataset']["state"]=='no-block')||(['done','fetched','refresh-wait'].includes(gatheredBlocks[i]['dataset']["state"]))) {
                        gatheredBlocks[i]['innerHTML'] = '';
                    } else {
                        ccRepeat = true;
                    }
                } else if (!gatheredBlocks[i]['dataset']['aid']) {
                    ccRepeat = true;
                }
            }
            if (cuc_cou < 50) {
                if (ccRepeat) {
                    let $this = this;
                    setTimeout(function () {
                        $this.clearUnsuitableCache(cuc_cou+1);
                    }, 100);
                }
            } else {
                endedCc = true;
            }
        } else {
            endedCc = true;
        }
    }


    excIdClUnpacker() {
        let excArr = [],
            cou = 0,
            currExcStr = '',
            curExcFirst = '';
        excArr['id'] = [];
        excArr['class'] = [];
        excArr['tag'] = [];
        if (excIdClass&&excIdClass.length > 0) {
            while (excIdClass[cou]) {
                currExcStr = excIdClass[cou];
                if (currExcStr.length > 0) {
                    curExcFirst = currExcStr.substring(0,1);
                    switch (curExcFirst) {
                        case '#':
                            if (currExcStr.length > 1) {
                                currExcStr = currExcStr.substring(1);
                                excArr['id'].push(currExcStr);
                            }
                            break;
                        case '.':
                            if (currExcStr.length > 1) {
                                currExcStr = currExcStr.substring(1);
                                excArr['class'].push(currExcStr);
                            }
                            break;
                        default:
                            excArr['tag'].push(currExcStr);
                            break;
                    }
                    cou++;
                }
            }
        }
        return excArr;
    }

    possibleTagsInCheckConfirmer(possibleTagsArray, possibleTagsInCheck) {
        if (possibleTagsArray.includes("LI")) {
            if (possibleTagsArray.includes("UL")) {
                possibleTagsInCheck.push("UL");
            }
            if (possibleTagsArray.includes("OL")) {
                possibleTagsInCheck.push("OL");
            }
        }

        return false;
    }

    textLengthGatherer(lordOfElementsLoc) {
        var possibleTagsArray;
        if (typeof tagsListForTextLength!=="undefined") {
            possibleTagsArray = tagsListForTextLength;
        } else {
            possibleTagsArray = ["P", "H1", "H2", "H3", "H4", "H5", "H6", "DIV", "BLOCKQUOTE", "INDEX", "ARTICLE", "SECTION"];
        }
        let possibleTagsInCheck = ["DIV", "INDEX", "SECTION"];

        this.possibleTagsInCheckConfirmer(possibleTagsArray, possibleTagsInCheck);
        let excArr = this.excIdClUnpacker(),
            textLength = 0,
            tlArray = [];

        function textLengthGathererRec(lordOfElementsLoc) {
            let allowed;
            let cou1;
            let classesArray;
            let countSuccess = 0;
            try {
                for (let i = 0; i < lordOfElementsLoc.children.length; i++) {
                    if (possibleTagsArray.includes(lordOfElementsLoc.children[i].tagName)
                        &&!lordOfElementsLoc.children[i].classList.contains("percentPointerClass")
                        &&lordOfElementsLoc.children[i].id!="toc_container"
                    ) {
                        if (possibleTagsInCheck.includes(lordOfElementsLoc.children[i].tagName)
                            &&(lordOfElementsLoc.children[i].children.length > 0)
                        ) {
                            allowed = true;
                            if (lordOfElementsLoc.children[i].id&&excArr['id'].length > 0) {
                                cou1 = 0;
                                while (excArr['id'][cou1]) {
                                    if (lordOfElementsLoc.children[i].id.toLowerCase()==excArr['id'][cou1].toLowerCase()) {
                                        allowed = false;
                                        break;
                                    }
                                    cou1++;
                                }
                            }

                            if (lordOfElementsLoc.children[i].classList.length > 0&&excArr['class'].length > 0) {
                                cou1 = 0;
                                while (excArr['class'][cou1]) {
                                    classesArray = excArr['class'][cou1].split('.');
                                    if (classesArray.every(className => lordOfElementsLoc.children[i].classList.contains(className))) {
                                        allowed = false;
                                        break;
                                    }
                                    cou1++;
                                }
                            }

                            if (excArr['tag'].length > 0) {
                                cou1 = 0;
                                while (excArr['tag'][cou1]) {
                                    if (lordOfElementsLoc.children[i].tagName.toLowerCase()==excArr['tag'][cou1].toLowerCase()) {
                                        allowed = false;
                                        break;
                                    }
                                    cou1++;
                                }
                            }

                            if (allowed) {
                                if (textLengthGathererRec(lordOfElementsLoc.children[i], excArr, possibleTagsArray, possibleTagsInCheck)) {
                                    countSuccess++;
                                    continue;
                                }
                            }
                        }
                        textLength = textLength + lordOfElementsLoc.children[i].innerText.length;
                        tlArray.push({
                            tag: lordOfElementsLoc.children[i].tagName,
                            length: lordOfElementsLoc.children[i].innerText.length,
                            lengthSum: textLength,
                            element: lordOfElementsLoc.children[i]
                        });
                        countSuccess++;
                    }
                }
            } catch (er) {
                console.log(er.message);
            }
            return countSuccess > 0;
        }

        textLengthGathererRec(lordOfElementsLoc);

        return {array: tlArray, length: textLength};
    }
};