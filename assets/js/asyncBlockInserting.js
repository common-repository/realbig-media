if (typeof window.jsInputerLaunch==='undefined') {window.jsInputerLaunch = -1;}

function asyncInsertingsInsertingFunction(insertingsArray) {
    let currentElementForInserting = 0;
    let currentElementToMove = 0;
    let positionElement = 0;
    let position = 0;
    let insertToAdd = 0;
    let postId = 0;
    let repeatSearch = 0;
    if (insertingsArray&&insertingsArray.length > 0) {
        for (let i = 0; i < insertingsArray.length; i++) {
            if (!insertingsArray[i]['used']||(insertingsArray[i]['used']&&insertingsArray[i]['used']==0)) {
                positionElement = insertingsArray[i]['position_element'];
                position = insertingsArray[i]['position'];
                insertToAdd = insertingsArray[i]['content'];
                postId = insertingsArray[i]['postId'];

                currentElementForInserting = document.querySelector(positionElement);

                currentElementToMove = document.querySelector('.coveredInsertings[data-id="'+postId+'"]');
                if (currentElementForInserting) {
                    if (position==0) {
                        currentElementForInserting.parentNode.insertBefore(currentElementToMove, currentElementForInserting);
                        currentElementToMove.classList.remove('coveredInsertings');
                        insertingsArray[i]['used'] = 1;
                    } else {
                        currentElementForInserting.parentNode.insertBefore(currentElementToMove, currentElementForInserting.nextSibling);
                        currentElementToMove.classList.remove('coveredInsertings');
                        insertingsArray[i]['used'] = 1;
                    }
                } else {
                    repeatSearch = 1;
                }
            }
        }
    }
    if (repeatSearch == 1) {
        setTimeout(function () {
            asyncInsertingsInsertingFunction(insertingsArray);
        }, 100)
    }
}

function insertingsFunctionLaunch() {
    if (window.jsInsertingsLaunch !== undefined&&jsInsertingsLaunch == 25) {
        asyncInsertingsInsertingFunction(insertingsArray);
    } else {
        setTimeout(function () {
            insertingsFunctionLaunch();
        }, 100)
    }
}

function setLongCache() {
    let xhttp = new XMLHttpRequest();
    let sendData = 'action=setLongCache&type=longCatching&_csrf='+rb_csrf;
    xhttp.onreadystatechange = function(redata) {
        if (this.readyState == 4 && this.status == 200) {
            console.log('long cache deployed');
        }
    };
    xhttp.open("POST", rb_ajaxurl, true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send(sendData);
}

function cachePlacing(alert_type, errorInfo=null) {
    let adBlocks = document.querySelectorAll('.percentPointerClass .' + block_classes.join(', .percentPointerClass .'));
    let curAdBlock;
    let okStates = ['done','refresh-wait','no-block','fetched'];
    /* let adId = -1; */
    let blockAid = null;
    let blockId;

    if (typeof cachedBlocksArray !== 'undefined'&&cachedBlocksArray&&cachedBlocksArray.length > 0&&adBlocks&&adBlocks.length > 0) {
        for (let i = 0; i < adBlocks.length; i++) {
            blockAid = adBlocks[i]['dataset']['aid'];

            if (!blockAid) {
                blockId = adBlocks[i]['dataset']['id'];
                if (cachedBlocksArray[blockId]) {
                    jQuery(adBlocks[i]).html(cachedBlocksArray[blockId]);
                }
            }
        }
    }

    if (alert_type&&alert_type=='high') {
        setLongCache();
    }
}

function saveContentBlock(contentContainer) {
    try {
        if (!gather_content) {
            console.log('content gather save function entered');
            let xhttp = new XMLHttpRequest();
            let sendData = 'action=RFWP_saveContentContainer&type=gatherContentBlock&data='+contentContainer+'&_csrf='+rb_csrf;
            xhttp.onreadystatechange = function(redata) {
                if (this.readyState == 4 && this.status == 200) {
                    console.log('content gather succeed');
                } else {
                    console.log('content gather gone wrong');
                }
            };
            xhttp.open("POST", rb_ajaxurl, true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(sendData);
        }
    } catch (er) {
        console.log('content gather error: '+er+';');
    }
}

window.gatherContentBlock = function() {
    let cPointers = null,
        cPointer = null,
        cPointerParent = null,
        cPointerParentString = null,
        cPointerParentStringItem = null,
        classWords = ['content','entry','post','wrap','description','taxonomy'],
        classChoosed = false;

    cPointers = document.querySelectorAll('.content_pointer_class');
    if (cPointers.length > 0) {
        for (let i = 0; i < cPointers.length; i++) {
            cPointer = cPointers[i];

            cPointerParentStringItem = null;
            if (window.jsInputerLaunch!==15) {
                return false;
            }
            cPointerParent = cPointer.parentElement;
            if (cPointerParent) {
                if (cPointerParent.classList.length > 0) {
                    cPointerParentStringItem = cPointerParent.tagName.toLowerCase() + '.' + cPointerParent.classList[0];
                    for (let j = 0; j < classWords.length; j++) {
                        for (let i = 0; i < cPointerParent.classList.length; i++) {
                            if (cPointerParent.classList[i].includes(classWords[j])) {
                                cPointerParentStringItem = cPointerParent.tagName.toLowerCase() + '.'+cPointerParent.classList[i];
                                classChoosed = true;
                                break;
                            }
                        }
                        if (classChoosed===true) {
                            break;
                        }
                    }

                    if (classChoosed===true) {
                        cPointerParentString = cPointerParentStringItem;
                        break;
                    }
                }
                if (cPointerParentStringItem && (!cPointerParentString || cPointerParentString !== cPointerParentStringItem)) {
                    cPointerParentString = cPointerParentStringItem;
                }
            }
        }

        if (cPointerParentString) {
            console.log('content gather content block detected');
            saveContentBlock(cPointerParentString);
        }
    } else {
        console.log('content gather delayed');
        setTimeout(function () {
            gatherContentBlock();
        }, 500);
    }
};

window.removeMarginClass = function(blockObject) {
    if (blockObject && typeof window.jsInputerLaunch !== 'undefined' && [15, 10].includes(window.jsInputerLaunch)) {
        let binderName,
            neededElement,
            currentDirection,
            seekerIterationCount,
            currentSubling;

        binderName = blockObject.dataset.rbinder;
        if (binderName) {
            seekerIterationCount = 0;
            currentDirection = 'before';
            do {
                seekerIterationCount++;
                currentSubling = blockObject.nextElementSibling;
                if (currentSubling&&currentSubling.classList.contains('rbinder-'+binderName)) {
                    neededElement = currentSubling;
                }
            } while (currentSubling&&!neededElement&&seekerIterationCount < 5);

            if (!neededElement) {
                seekerIterationCount = 0;
                currentDirection = 'after';
                do {
                    seekerIterationCount++;
                    currentSubling = blockObject.previousElementSibling;
                    if (currentSubling&&currentSubling.classList.contains('rbinder-'+binderName)) {
                        neededElement = currentSubling;
                    }
                } while (currentSubling&&!neededElement&&seekerIterationCount < 5);
            }
            if (neededElement) {
                if (currentDirection === 'before') {
                    neededElement.classList.remove('rfwp_removedMarginTop');
                } else {
                    neededElement.classList.remove('rfwp_removedMarginBottom');
                }
            }
        }
    }

    return false;
};