import { useQuery } from '@tanstack/react-query'
import { useSearchParams, useParams, useLocation } from 'react-router-dom'
import ProductsPage from '@/pages/products/index'
import CreateProductPage from '@/pages/products/create'
import EditProductPage from '@/pages/products/edit'
import ShowProductPage from '@/pages/products/show'
import { RoutePending } from '@/components/route-pending'
import NotFoundError from '@/pages/errors/not-found-error'
import GeneralError from '@/pages/errors/general-error'
import { normalizePaginatedPayload } from '@/lib/api-utils'
import { productsApi } from '@/services/products-api'
import type { Product } from '@/pages/products/types'

export default function ProductsRoute() {
  const [searchParams] = useSearchParams()
  const { id } = useParams()
  const location = useLocation()
  const filters = Object.fromEntries(searchParams.entries())

  const isCreate = location.pathname.endsWith('/create')
  const isEdit = !!id && location.pathname.includes(`/${id}/edit`)
  const isShow = !!id && !isEdit && location.pathname.includes(`/products/${id}`)

  const {
    data: listData,
    isLoading: listLoading,
    isError: listError,
  } = useQuery({
    queryKey: ['products', filters],
    queryFn: () => productsApi.list(filters),
    enabled: !isCreate && !isEdit && !isShow,
  })

  const {
    data: productData,
    isLoading: productLoading,
    isError: productError,
  } = useQuery({
    queryKey: ['products', id],
    queryFn: () => productsApi.detail(id!),
    enabled: isEdit || isShow,
  })

  const products = normalizePaginatedPayload<Product>(listData)
  const stats = listData?.stats ?? { total: 0, lowStock: 0, outOfStock: 0 }

  if (isEdit) {
    if (productLoading) return <RoutePending />
    if (productError || !productData) return <NotFoundError />
    return <EditProductPage product={productData} />
  }

  if (isCreate) {
    return <CreateProductPage />
  }

  if (isShow) {
    if (productLoading) return <RoutePending />
    if (productError || !productData) return <NotFoundError />
    return <ShowProductPage product={productData} />
  }

  if (listLoading) return <RoutePending />
  if (listError) return <GeneralError minimal />

  return <ProductsPage products={products} stats={stats} filters={filters} />
}
