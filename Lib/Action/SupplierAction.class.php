<?php
class SupplierAction extends CommonAction {
	//过滤查询字段

	function _filter(&$map) {
		$map['name'] = array('like', "%" . $_POST['name'] . "%");
		$map['letter'] = array('like', "%" . $_POST['letter'] . "%");
		$map['is_del'] = array('eq', '0');
		if (!empty($_POST['tag'])) {
			$map['group'] = $_POST['tag'];
		}
		$map['is_del'] = array('eq', '0');
	}

	function index() {
		$model = M("Supplier");
		$where['is_del']=0;
		$list = $model -> where($where) -> select();
		$this -> assign('list', $list);
		$tag_data = D("SystemTag") -> get_data_list();
		$new = array();
		foreach ($tag_data as $val) {
			$new[$val['row_id']] = $new[$val['row_id']] . $val['tag_id'] . ",";
		}
		$this -> assign('tag_data', $new);
		$this -> tag_list();
		$this -> display();
		return;
	}

	function export() {
		$model = M("Supplier");
		$where['user_id'] = array('eq', get_user_id());
		$list = $model -> where($where) -> select();

		Vendor('Excel.PHPExcel');
		//导入thinkphp第三方类库

		$inputFileName = C("SAVE_PATH") . "templete/Supplier.xlsx";
		$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

		$objPHPExcel -> getProperties() -> setCreator("小薇") -> setLastModifiedBy("小薇") -> setTitle("Office 2007 XLSX Test Document") -> setSubject("Office 2007 XLSX Test Document") -> setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") -> setKeywords("office 2007 openxml php") -> setCategory("Test result file");
		// Add some data
		$i = 1;
		//dump($list);
		foreach ($list as $val) {
			$i++;
			$objPHPExcel -> setActiveSheetIndex(0) -> setCellValue("A$i", $val["name"]) -> setCellValue("B$i", $val["short"]) -> setCellValue("C$i", $val["account"]) -> setCellValue("D$i", $val["tax_no"]) -> setCellValue("E$i", $val["payment"]) -> setCellValue("F$i", $val["address"]) -> setCellValue("G$i", $val["contact"]) -> setCellValue("H$i", $val["email"]) -> setCellValue("I$i", $val["office_tel"]) -> setCellValue("J$i", $val["mobile_tel"]) -> setCellValue("J$i", $val["fax"])-> setCellValue("L$i", $val["im"])-> setCellValue("M$i", $val["remark"]);
		}
		// Rename worksheet
		$objPHPExcel -> getActiveSheet() -> setTitle('Supplier');

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel -> setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $file_name . '"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter -> save('php://output');
		exit ;
	}

	public function import() {
		$save_path = C('SAVE_PATH');
		$opmode = $_POST["opmode"];
		if ($opmode == "import") {
			import("@.ORG.Util.UploadFile");
			$upload = new UploadFile();
			$upload -> savePath = $save_path;
			$upload -> allowExts = array('xlsx');
			$upload -> saveRule = uniqid;
			$upload -> autoSub = false;

			if (!$upload -> upload()) {
				$this -> error($upload -> getErrorMsg());
			} else {
				//取得成功上传的文件信息
				$uploadList = $upload -> getUploadFileInfo();
				Vendor('Excel.PHPExcel');
				//导入thinkphp第三方类库

				$inputFileName = $save_path . $uploadList[0]["savename"];
				$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
				$sheetData = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
				$model = M("Supplier");
				for ($i = 2; $i <= count($sheetData); $i++) {
					$data = array();
					$data['name'] = $sheetData[$i]["A"];
					$data['short'] = $sheetData[$i]["B"];
					$data['letter'] = get_letter($sheetData[$i]["B"]);
					$data['account'] = $sheetData[$i]["C"];
					$data['tax_no'] = $sheetData[$i]["D"];
					$data['payment'] = $sheetData[$i]["E"];
					$data['address'] = $sheetData[$i]["F"];
					$data['contact'] = $sheetData[$i]["G"];
					$data['email'] = $sheetData[$i]["H"];
					$data['office_tel'] = $sheetData[$i]["I"];
					$data['mobile_tel'] = $sheetData[$i]["J"];
					$data['fax'] = $sheetData[$i]["K"];
					$data['im'] = $sheetData[$i]["L"];
					$data['is_del'] = 0;
					$model -> add($data);
				}
				//dump($sheetData);
				if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/" . $inputFileName)) {
					unlink($_SERVER["DOCUMENT_ROOT"] . "/" . $inputFileName);
				}
				$this -> assign('jumpUrl', $this -> _get_return_url());
				$this -> success('导入成功！');
			}
		} else {
			$this -> display();
		}
	}

	function mark() {
		$id = $_REQUEST["id"];
		$val = $_REQUEST["val"];
		$field = 'group';
		$result = $this -> set_field($id, $field, $val);
		if ($result !== false) {
			$this -> assign('jumpUrl', $this -> _get_return_url());
			$this -> success('操作成功!');
		} else {
			//失败提示
			$this -> error('操作失败!');
		}
	}

	function tag_list() {
		$model = D("SystemTag");
		$tag_list = $model -> get_list();
		$this -> assign("tag_list", $tag_list);
	}

	function insert() {
		$ajax = $_POST['ajax'];
		$model = D('Supplier');
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$model -> __set('letter', get_letter($model -> __get('name')));
		$model -> __set('user_id', get_user_id());
		//保存当前数据对象
		$list = $model -> add();
		if ($list !== false) {//保存成功
			if ($ajax || $this -> isAjax())
				$this -> ajaxReturn($list, "新增成功", 1);
			$this -> assign('jumpUrl', $this -> _get_return_url());
			$this -> success('新增成功!');
		} else {
			//失败提示
			$this -> error('新增失败!');
		}
	}

	function update() {
		$ajax = $_POST['ajax'];
		$id = $_POST['id'];
		$model = D("Supplier");
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		$model -> __set('letter', get_letter($model -> __get('name')));
		// 更新数据
		$list = $model -> save();
		if (false !== $list) {
			//成功提示
			$this -> assign('jumpUrl', $this -> _get_return_url());
			$this -> success('编辑成功!');
		} else {
			//错误提示
			$this -> error('编辑失败!');
		}
	}

	function del() {
		if (!empty($_POST['supplier_id'])) {
			$model = M("Supplier");
			$Supplier_list = $_POST['supplier_id'];
			if (!is_array($Supplier_list)) {
				$Supplier_list = explode(",", $Supplier_list);
			}
			$where['id'] = array('in', $Supplier_list);
			$where['user_id'] = get_user_id();
			$model -> where($where) -> delete();
			$model = D("SystemTag");
			$result = $model -> del_data_by_row($_POST['supplier_id'], 'Supplier');
		};
		if ($result !== false) {//保存成功
			if ($ajax || $this -> isAjax())
				$this -> ajaxReturn($list, "操作成功", 1);
			$this -> assign('jumpUrl', $this -> _get_return_url());
			$this -> success('操作成功!');
		} else {
			//失败提示
			$this -> error('操作失败!');
		}
	}

	function read() {
			$model = M('Supplier');
			$id = $_REQUEST[$model -> getPk()];
			$vo = $model -> getById($id);
			$this -> assign('vo', $vo);
			$this -> display();
	}

	function set_tag(){
		if (!empty($_POST['supplier_id'])) {
			$model = D("SystemTag");
			$model -> del_data_by_row($_POST['supplier_id'], 'supplier');
			if (!empty($_POST['tag'])) {
				$result = $model -> set_tag($_POST['supplier_id'], $_POST['tag'], "supplier");
			}
		};
		if (!empty($_POST['new_tag'])) {
			$model = M("SystemTag");
			$model -> module = MODULE_NAME;
			$model -> name = $_POST['new_tag'];
			$model -> is_del = 0;
			$model -> user_id = get_user_id();
			$new_tag_id = $model -> add();
			if (!empty($_POST['supplier_id'])) {
				$model = D("SystemTag");
				$result = $model -> set_tag($_POST['supplier_id'], $new_tag_id, "supplier");
			}
		};
		if ($result !== false) {//保存成功
			if ($ajax || $this -> isAjax())
				$this -> ajaxReturn($list, "操作成功", 1);
			$this -> assign('jumpUrl', $this -> _get_return_url());
			$this -> success('操作成功!');
		} else {
			//失败提示
			$this -> error('操作失败!');
		}
	}

	function json(){
		header("Content-Type:text/html; charset=utf-8");
		$key = $_REQUEST['key'];

		$model = M("Supplier");
		$where['name'] = array('like', "%" . $key . "%");
		$where['letter'] = array('like', "%" . $key . "%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$map['status'] = 1;
		$list = $model -> where($map) -> field('id,name') -> select();
		exit(json_encode($list));
	}

	public function winpop() {
		$node = M("Supplier");
		$menu = array();
		$menu = $node -> where($where) -> field('id,name') -> select();
		$tree = list_to_tree($menu);
		//dump($node);
		$this -> assign('menu', popup_tree_menu($tree));
		$this -> display();
	}
}
?>