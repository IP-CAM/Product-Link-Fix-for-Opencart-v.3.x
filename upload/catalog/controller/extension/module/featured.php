<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featured');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

		if (!empty($setting['product'])) {
			$products = array_slice($setting['product'], 0, (int)$setting['limit']);

			foreach ($products as $product_id) {
				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info) {
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $setting['width'], $setting['height']);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
					}

					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$price = false;
					}

					if ((float)$product_info['special']) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$special = false;
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = $product_info['rating'];
					} else {
						$rating = false;
					}

                    $this->load->model('catalog/category');
                    $getCategories = $this->model_catalog_product->getCategories($product_id);
                    $path = '';

                    $categoriesPaths = array();
                    $max_count = 0;
                    foreach ($getCategories as $getCategory) {
                        $categoriesPaths[] = $this->model_catalog_category->getCategoryPathHighestLevel($getCategory['category_id']);
                    }

                    foreach ($categoriesPaths as $k => $categoriesPath) {
                        if ($max_count < count($categoriesPath)) {
                            $max_count = count($categoriesPath);
                        }
                     }

                    foreach ($categoriesPaths as $key => $categoriesPath) {
                        if ($max_count > count($categoriesPath)) {
                            unset($categoriesPaths[$key]);
                        }
                    }

                    if (!empty($categoriesPaths)) {
                        $min_category_id = 1000000000;
                        $currentCategoryPaths = array();

                        foreach ($categoriesPaths as $key => $item) {
                            if (isset($item[0]) && isset($item[0]['path_id']) && $item[0]['path_id'] < $min_category_id) {
                                $min_category_id = $item[0]['path_id'];
                                $currentCategoryPaths = $item;
                            }
                        }

                        foreach ($currentCategoryPaths as $kk => $currentCategoryPath) {
                            if ($kk != (count($currentCategoryPaths) - 1)) {
                                $path .= $currentCategoryPath['path_id'] . '_';
                            } else {
                                $path .= $currentCategoryPath['path_id'];
                            }
                        }
                    }

                    if(!empty($path)) {
                        $product_link = $this->url->link('product/product', 'path=' . $path . '&product_id=' . $product_info['product_id']);
                    } else {
                        $product_link = $this->url->link('product/product', 'product_id=' . $product_info['product_id']);
                    }

					$data['products'][] = array(
						'product_id'  => $product_info['product_id'],
						'thumb'       => $image,
						'name'        => $product_info['name'],
						'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
						'price'       => $price,
						'special'     => $special,
						'tax'         => $tax,
						'rating'      => $rating,
						'href'        => $product_link
					);
				}
			}
		}

		if ($data['products']) {
			return $this->load->view('extension/module/featured', $data);
		}
	}
}