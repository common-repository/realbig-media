var nReadyBlock = false;
var fetchedCounter = 0;

function sendReadyBlocksNew(blocks) {
    if (!cache_devices) {
        let xhttp = new XMLHttpRequest();
        let sendData = 'action=saveAdBlocks&type=blocksGethering&data='+blocks+'&_csrf='+rb_csrf;
        xhttp.onreadystatechange = function(redata) {
            if (this.readyState == 4 && this.status == 200) {
                console.log('cache succeed');
            }
        };
        xhttp.open("POST", rb_ajaxurl, true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(sendData);
    }
}

function gatherReadyBlocks() {
    if (block_classes && block_classes.length) {
        let blocks = {};
        let counter1 = 0;
        let gatheredBlocks = document.querySelectorAll('.' + block_classes.join(', .'));
        let checker = 0;
        let adContent = '';
        let curState = '';
        let thisData = [];
        let sumData = [];
        let newBlocks = '';
        let thisDataString = '';

        if (gatheredBlocks.length > 0) {
            blocks.data = {};

            for (let i = 0; i < gatheredBlocks.length; i++) {
                curState = gatheredBlocks[i]['dataset']["state"].toLowerCase();
                checker = 0;
                if (curState&&gatheredBlocks[i]['innerHTML'].length > 0&&gatheredBlocks[i]['dataset']['aid'] > 0&&curState!='no-block') {
                    if (gatheredBlocks[i]['innerHTML'].length > 0) {
                        checker = 1;
                    }
                    if (checker==1) {
                        blocks.data[counter1] = {id:gatheredBlocks[i]['dataset']['id'],code:gatheredBlocks[i]['dataset']['aid']};
                        counter1++;
                    }
                }
            }

            blocks = JSON.stringify(blocks);
            sendReadyBlocksNew(blocks);
        }
    } else nReadyBlock = true;
}

function timeBeforeGathering() {
    if (block_classes && block_classes.length > 0)
    {
        let gatheredBlocks = document.querySelectorAll('.' + block_classes.join(', .'));
        let okStates = ['done','refresh-wait','no-block','fetched'];
        let curState = '';

        for (let i = 0; i < gatheredBlocks.length; i++) {
            if (!gatheredBlocks[i]['dataset']["state"]) {
                nReadyBlock = true;
                break;
            } else {
                curState = gatheredBlocks[i]['dataset']["state"].toLowerCase();
                if (!okStates.includes(curState)) {
                    nReadyBlock = true;
                    break;
                } else if (curState=='fetched'&&fetchedCounter < 3) {
                    fetchedCounter++;
                    nReadyBlock = true;
                    break;
                }
            }
        }
    }
    else nReadyBlock = true;

    if (nReadyBlock == true) {
        nReadyBlock = false;
        setTimeout(timeBeforeGathering,2000);
    } else {
        gatherReadyBlocks();
    }
}

function launchTimeBeforeGathering() {
    if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
        timeBeforeGathering();
    } else {
        setTimeout(launchTimeBeforeGathering,100);
    }
}
launchTimeBeforeGathering();
