(function () {
    function importer(debug) {
        if (arguments.length == 0) {
            debug = false;
        }
        this.initialize.call(this, debug);
    }

    importer.prototype = {
        //
        initialize: function (debug) {
            this.debug = debug;
        },
        //选择课本所在的目录
        getSelectedPath: function () {
            if (this.debug) {
                return "D:\\地理七年级（上册）";
            }
            return window.external.GetSelectedPath();
        },
        //获取目录的文件夹名称
        getDirectoryName: function (path) {
            if (this.debug) {
                return "地理七年级（上册）";
            }
            return window.external.GetDirectoryName(path);
        },
        //检测目录是否存在
        directoryExists: function (path) {
            if (this.debug) {
                return true;
            }
            return window.external.DirectoryExists(path);
        },
        //打开文件
        openFile: function (file) {
            if (this.debug) {
                return;
            }
            window.external.OpenFile(file);
        },
        //打开目录
        openDirectory: function (path) {
            if (this.debug) {
                return;
            }
            window.external.OpenDirectory(path);
        },
        //读取文件内容
        readFile: function (file) {
            if (this.debug) {
                return "";
            }
            return window.external.ReadFile(file);
        },
        //保存书本标识
        saveBookId: function (path, bookId) {
            if (this.debug) {
                return true;
            }
            return window.external.SaveBookId(path, parseInt(bookId));
        },
        //保存目录标识
        saveContentIds: function (path, contentIds) {
            if (this.debug) {
                return true;
            }
            return window.external.SaveContentIds(path, contentIds);
        },
        //保存文件状态
        saveFileProcessed: function (path, file) {
            if (this.debug) {
                return true;
            }
            return window.external.SaveFileProcessed(path, file);
        },
        //加载课本和目录标识
        loadBookIdAndContentId: function (path, file) {
            if (this.debug) {
                return {"BookId": 1, "ContentId": 2};
            }
            var ids = window.external.LoadBookIdAndContentId(path, file);
            return JSON.parse(ids);
        },
        //生成课本项目文件
        loadBook: function (path) {
            var book = window.external.LoadBook(path);
            return JSON.parse(book);
        },
        //是否有课本标识
        hasBookId: function (path) {
            if (this.debug) {
                return true;
            }
            return window.external.HasBookId(path);
        },
        //是否有目录标识
        hasContentIds: function (path) {
            if (this.debug) {
                return true;
            }
            return window.external.HasContentIds(path);
        },
        //将Word转换成Html
        wordToHtml: function (file) {
            return window.external.WordToHtml(file);
        },
        //将Html转换成题目
        generateQuestions: function (path) {
            var questions = window.external.GenerateQuestions(path);
            return JSON.parse(questions);
        },
        //调整窗口大小
        resize: function (width, height) {
            window.external.Resize(width, height);
        },
        //窗口最大化
        maximumSize: function () {
            window.external.MaximumSize();
        },
        //窗口最小化
        minimumSize: function () {
            window.external.MinimumSize();
        }
    };

    if (typeof window.Importer == "undefined") {
        window.Importer = new importer();
    }
})();